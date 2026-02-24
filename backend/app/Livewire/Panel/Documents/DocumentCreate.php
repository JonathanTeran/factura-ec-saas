<?php

namespace App\Livewire\Panel\Documents;

use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\DocumentItem;
use App\Models\SRI\WithholdingDetail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DocumentCreate extends Component
{
    public ?ElectronicDocument $document = null;
    public bool $isEdit = false;

    // Datos del documento
    public string $document_type = '01';
    public ?int $customer_id = null;
    public ?int $branch_id = null;
    public ?int $emission_point_id = null;
    public string $issue_date = '';
    public string $due_date = '';
    public string $notes = '';

    // Items (para facturas, NC, ND)
    public array $items = [];

    // Búsqueda
    public string $customerSearch = '';
    public string $productSearch = '';

    // Totales
    public float $subtotal = 0;
    public float $subtotal_0 = 0;
    public float $subtotal_5 = 0;
    public float $subtotal_12 = 0;
    public float $subtotal_15 = 0;
    public float $total_discount = 0;
    public float $total_tax = 0;
    public float $tip = 0;
    public float $total = 0;

    // Forma de pago
    public array $payment_methods = [];

    // Notas de Crédito / Débito
    public ?int $related_document_id = null;
    public string $relatedDocumentSearch = '';
    public string $modification_reason = '';

    // Retenciones
    public array $withholding_details = [];

    // Guía de Remisión
    public string $carrier_ruc = '';
    public string $carrier_name = '';
    public string $carrier_plate = '';
    public string $origin_address = '';
    public string $destination_address = '';
    public string $destination_ruc = '';
    public string $destination_name = '';
    public string $transport_start_date = '';

    protected $listeners = ['customerSelected', 'productSelected'];

    public function mount(?ElectronicDocument $document = null): void
    {
        if (!$document && !$this->ensureActiveSubscription()) {
            return;
        }

        if (!$document && !$this->ensureCreationRequirements()) {
            return;
        }

        // Detectar tipo de documento desde la ruta
        $routeName = request()->route()?->getName() ?? '';
        $this->document_type = match (true) {
            str_contains($routeName, 'credit-notes') => '04',
            str_contains($routeName, 'debit-notes') => '05',
            str_contains($routeName, 'retention') => '07',
            str_contains($routeName, 'guides') => '06',
            default => '01',
        };

        $this->issue_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        $this->transport_start_date = now()->format('Y-m-d');

        // Obtener branch y emission point por defecto
        $user = auth()->user();
        $company = $user->tenant->companies()->first();

        if ($company) {
            $branch = $company->branches()->where('is_main', true)->first()
                ?? $company->branches()->first();

            if ($branch) {
                $this->branch_id = $branch->id;
                $emissionPoint = $branch->emissionPoints()->first();
                if ($emissionPoint) {
                    $this->emission_point_id = $emissionPoint->id;
                }
            }
        }

        // Agregar forma de pago por defecto (solo para facturas)
        if ($this->document_type === '01') {
            $this->payment_methods = [
                ['code' => '01', 'amount' => 0, 'term' => 0, 'time_unit' => 'dias'],
            ];
        }

        if ($document && $document->exists) {
            if ($document->tenant_id !== $user->tenant_id) {
                abort(403);
            }

            $this->document = $document;

            if (!$this->document->isEditable()) {
                $this->redirect(route('panel.documents.show', $this->document));
                return;
            }

            $this->isEdit = true;
            $this->loadDocument();
        }
    }

    private function loadDocument(): void
    {
        $this->document_type = $this->document->document_type->value;
        $this->customer_id = $this->document->customer_id;
        $this->branch_id = $this->document->branch_id;
        $this->emission_point_id = $this->document->emission_point_id;
        $this->issue_date = $this->document->issue_date->format('Y-m-d');
        $this->due_date = $this->document->due_date?->format('Y-m-d') ?? '';
        $this->notes = $this->document->notes ?? '';
        $this->tip = (float) $this->document->tip;
        $this->payment_methods = $this->document->payment_methods ?? [];
        $this->related_document_id = $this->document->related_document_id;
        $this->modification_reason = $this->document->additional_info['modification_reason'] ?? '';

        // Cargar items
        $this->items = $this->document->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'main_code' => $item->main_code,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'discount_percent' => (float) $item->discount_percentage,
                'tax_code' => $item->tax_percentage_code,
                'subtotal' => (float) $item->subtotal,
                'tax_value' => (float) $item->tax_value,
                'total' => (float) $item->total,
            ];
        })->toArray();

        // Cargar detalles de retención
        if ($this->document_type === '07') {
            $this->withholding_details = $this->document->withholdingDetails->map(function ($detail) {
                return [
                    'tax_type' => $detail->tax_type,
                    'tax_code' => $detail->tax_code,
                    'withholding_code' => $detail->withholding_code,
                    'withholding_percentage' => (float) $detail->withholding_percentage,
                    'base_amount' => (float) $detail->base_amount,
                    'withheld_amount' => (float) $detail->withheld_amount,
                ];
            })->toArray();
        }

        $this->calculateTotals();
    }

    // ==================== HELPERS DE TIPO ====================

    public function getDocumentTitleProperty(): string
    {
        if ($this->isEdit) {
            return 'Editar Documento';
        }

        return match ($this->document_type) {
            '01' => 'Nueva Factura',
            '04' => 'Nueva Nota de Crédito',
            '05' => 'Nueva Nota de Débito',
            '06' => 'Nueva Guía de Remisión',
            '07' => 'Nueva Retención',
            default => 'Nuevo Documento',
        };
    }

    public function getDocumentSubtitleProperty(): string
    {
        if ($this->isEdit) {
            return 'Modifica los datos del documento';
        }

        return match ($this->document_type) {
            '01' => 'Crea una nueva factura electrónica',
            '04' => 'Emite una nota de crédito sobre una factura existente',
            '05' => 'Emite una nota de débito sobre una factura existente',
            '06' => 'Crea una guía de remisión para transporte de mercadería',
            '07' => 'Emite un comprobante de retención',
            default => 'Crea un nuevo documento electrónico',
        };
    }

    public function getEmitButtonTextProperty(): string
    {
        return match ($this->document_type) {
            '01' => 'Emitir factura',
            '04' => 'Emitir nota de crédito',
            '05' => 'Emitir nota de débito',
            '06' => 'Emitir guía de remisión',
            '07' => 'Emitir retención',
            default => 'Emitir documento',
        };
    }

    public function getIsInvoiceProperty(): bool
    {
        return $this->document_type === '01';
    }

    public function getIsCreditNoteProperty(): bool
    {
        return $this->document_type === '04';
    }

    public function getIsDebitNoteProperty(): bool
    {
        return $this->document_type === '05';
    }

    public function getIsRetentionProperty(): bool
    {
        return $this->document_type === '07';
    }

    public function getIsGuideProperty(): bool
    {
        return $this->document_type === '06';
    }

    public function getNeedsRelatedDocumentProperty(): bool
    {
        return in_array($this->document_type, ['04', '05']);
    }

    public function getNeedsItemsProperty(): bool
    {
        return in_array($this->document_type, ['01', '04', '05']);
    }

    public function getNeedsPaymentMethodsProperty(): bool
    {
        return $this->document_type === '01';
    }

    // ==================== BÚSQUEDAS ====================

    public function getCustomersProperty()
    {
        if (strlen($this->customerSearch) < 2) {
            return collect();
        }

        return Customer::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('identification', 'like', "%{$this->customerSearch}%")
                    ->orWhere('email', 'like', "%{$this->customerSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function getProductsProperty()
    {
        if (strlen($this->productSearch) < 2) {
            return collect();
        }

        return Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('main_code', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function getRelatedDocumentsProperty()
    {
        if (strlen($this->relatedDocumentSearch) < 2) {
            return collect();
        }

        return ElectronicDocument::where('tenant_id', auth()->user()->tenant_id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->where(function ($q) {
                $q->where('sequential', 'like', "%{$this->relatedDocumentSearch}%")
                    ->orWhereHas('customer', fn($cq) =>
                        $cq->where('name', 'like', "%{$this->relatedDocumentSearch}%")
                            ->orWhere('identification', 'like', "%{$this->relatedDocumentSearch}%")
                    );
            })
            ->with('customer')
            ->latest('issue_date')
            ->limit(10)
            ->get();
    }

    public function getSelectedRelatedDocumentProperty()
    {
        return $this->related_document_id
            ? ElectronicDocument::with('customer')->find($this->related_document_id)
            : null;
    }

    public function getBranchesProperty()
    {
        $company = auth()->user()->tenant->companies()->first();
        return $company ? $company->branches()->where('is_active', true)->get() : collect();
    }

    public function getEmissionPointsProperty()
    {
        if (!$this->branch_id) {
            return collect();
        }

        return EmissionPoint::where('branch_id', $this->branch_id)
            ->where('is_active', true)
            ->get();
    }

    public function getSelectedCustomerProperty()
    {
        return $this->customer_id
            ? Customer::find($this->customer_id)
            : null;
    }

    // ==================== ACCIONES DE CLIENTE ====================

    public function selectCustomer(int $customerId): void
    {
        $this->customer_id = $customerId;
        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
    }

    // ==================== DOCUMENTO RELACIONADO (NC/ND) ====================

    public function selectRelatedDocument(int $docId): void
    {
        $doc = ElectronicDocument::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer', 'items'])
            ->find($docId);

        if (!$doc) {
            return;
        }

        $this->related_document_id = $doc->id;
        $this->relatedDocumentSearch = '';

        // Auto-seleccionar el cliente del documento original
        $this->customer_id = $doc->customer_id;

        // Para notas de crédito, copiar los items del documento original
        if ($this->document_type === '04') {
            $this->items = $doc->items->map(function ($item) {
                return [
                    'id' => null,
                    'product_id' => $item->product_id,
                    'main_code' => $item->main_code,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount' => (float) $item->discount,
                    'discount_percent' => (float) $item->discount_percentage,
                    'tax_code' => $item->tax_percentage_code,
                    'subtotal' => (float) $item->subtotal,
                    'tax_value' => (float) $item->tax_value,
                    'total' => (float) $item->total,
                ];
            })->toArray();

            $this->calculateTotals();
        }
    }

    public function clearRelatedDocument(): void
    {
        $this->related_document_id = null;
        if ($this->document_type === '04') {
            $this->items = [];
            $this->calculateTotals();
        }
    }

    // ==================== RETENCIONES ====================

    public function addWithholdingDetail(): void
    {
        $this->withholding_details[] = [
            'tax_type' => 'renta',
            'tax_code' => '',
            'withholding_code' => '',
            'withholding_percentage' => 0,
            'base_amount' => 0,
            'withheld_amount' => 0,
        ];
    }

    public function removeWithholdingDetail(int $index): void
    {
        unset($this->withholding_details[$index]);
        $this->withholding_details = array_values($this->withholding_details);
        $this->calculateWithholdingTotal();
    }

    public function updatedWithholdingDetails(): void
    {
        $this->calculateWithholdingTotal();
    }

    private function calculateWithholdingTotal(): void
    {
        $this->total = 0;
        foreach ($this->withholding_details as &$detail) {
            $detail['withheld_amount'] = round(
                ((float) $detail['base_amount']) * ((float) $detail['withholding_percentage']) / 100,
                2
            );
            $this->total += $detail['withheld_amount'];
        }
    }

    // ==================== ITEMS / PRODUCTOS ====================

    public function addProduct(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            return;
        }

        // Verificar si ya existe el producto
        $existingIndex = collect($this->items)->search(fn($item) => $item['product_id'] === $productId);

        if ($existingIndex !== false) {
            $this->items[$existingIndex]['quantity']++;
            $this->recalculateItem($existingIndex);
        } else {
            $this->items[] = [
                'id' => null,
                'product_id' => $product->id,
                'main_code' => $product->main_code,
                'description' => $product->name,
                'quantity' => 1,
                'unit_price' => (float) $product->unit_price,
                'discount' => 0,
                'discount_percent' => 0,
                'tax_code' => $product->tax_percentage_code,
                'subtotal' => (float) $product->unit_price,
                'tax_value' => $product->calculateTax($product->unit_price),
                'total' => (float) $product->unit_price + $product->calculateTax($product->unit_price),
            ];
        }

        $this->productSearch = '';
        $this->calculateTotals();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }

    public function updateItemQuantity(int $index, $quantity): void
    {
        $this->items[$index]['quantity'] = max(0.0001, (float) $quantity);
        $this->recalculateItem($index);
        $this->calculateTotals();
    }

    public function updateItemPrice(int $index, $price): void
    {
        $this->items[$index]['unit_price'] = max(0, (float) $price);
        $this->recalculateItem($index);
        $this->calculateTotals();
    }

    public function updateItemDiscount(int $index, $discount): void
    {
        $this->items[$index]['discount_percent'] = min(100, max(0, (float) $discount));
        $this->recalculateItem($index);
        $this->calculateTotals();
    }

    private function recalculateItem(int $index): void
    {
        $item = &$this->items[$index];

        $subtotalBruto = $item['quantity'] * $item['unit_price'];
        $item['discount'] = round($subtotalBruto * ($item['discount_percent'] / 100), 2);
        $item['subtotal'] = round($subtotalBruto - $item['discount'], 2);

        $taxRate = $this->getTaxRate($item['tax_code']);
        $item['tax_value'] = round($item['subtotal'] * ($taxRate / 100), 2);
        $item['total'] = $item['subtotal'] + $item['tax_value'];
    }

    private function getTaxRate(string $code): float
    {
        return match ($code) {
            '0' => 0,
            '5' => 5,
            '2' => 12,
            '3' => 14,
            '4' => 15,
            default => 0,
        };
    }

    private function calculateTotals(): void
    {
        $this->subtotal_0 = 0;
        $this->subtotal_5 = 0;
        $this->subtotal_12 = 0;
        $this->subtotal_15 = 0;
        $this->total_discount = 0;
        $this->total_tax = 0;

        foreach ($this->items as $item) {
            $this->total_discount += $item['discount'];
            $this->total_tax += $item['tax_value'];

            match ($item['tax_code']) {
                '0', '6', '7' => $this->subtotal_0 += $item['subtotal'],
                '5' => $this->subtotal_5 += $item['subtotal'],
                '2' => $this->subtotal_12 += $item['subtotal'],
                '3', '4' => $this->subtotal_15 += $item['subtotal'],
                default => $this->subtotal_15 += $item['subtotal'],
            };
        }

        $this->subtotal = $this->subtotal_0 + $this->subtotal_5 + $this->subtotal_12 + $this->subtotal_15;
        $this->total = $this->subtotal + $this->total_tax + $this->tip;

        // Actualizar monto en forma de pago
        if (count($this->payment_methods) === 1) {
            $this->payment_methods[0]['amount'] = $this->total;
        }
    }

    // ==================== FORMAS DE PAGO ====================

    public function addPaymentMethod(): void
    {
        $this->payment_methods[] = [
            'code' => '01',
            'amount' => 0,
            'term' => 0,
            'time_unit' => 'dias',
        ];
    }

    public function removePaymentMethod(int $index): void
    {
        unset($this->payment_methods[$index]);
        $this->payment_methods = array_values($this->payment_methods);
    }

    // ==================== GUARDAR ====================

    public function saveDraft(): void
    {
        if (!$this->ensureActiveSubscription()) {
            return;
        }

        if (!$this->ensureCreationRequirements()) {
            return;
        }

        $this->save(DocumentStatus::DRAFT);
    }

    public function saveAndProcess(): void
    {
        if (!$this->ensureActiveSubscription()) {
            return;
        }

        if (!$this->ensureCreationRequirements()) {
            return;
        }

        if (!$this->ensureEmissionRequirements()) {
            return;
        }

        $this->save(DocumentStatus::PROCESSING, true);
    }

    private function ensureActiveSubscription(): bool
    {
        $tenant = auth()->user()?->tenant;

        if ($tenant?->activeSubscription) {
            return true;
        }

        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Necesitas una suscripción activa para crear documentos.',
        ]);

        $this->redirect(route('panel.settings.billing'), navigate: true);
        return false;
    }

    private function ensureCreationRequirements(): bool
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Configura primero los datos del emisor en Configuración > Empresa.',
            ]);
            $this->redirect(route('panel.settings.company'), navigate: true);
            return false;
        }

        $checklist = $company->emissionReadinessChecklist();

        if (!$checklist['basic_data']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Completa los datos fiscales del emisor (RUC, razón social, dirección y contacto).',
            ]);
            $this->redirect(route('panel.settings.company'), navigate: true);
            return false;
        }

        if (!$checklist['establishments']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Configura al menos un establecimiento con punto de emisión antes de crear documentos.',
            ]);
            $this->redirect(route('panel.settings.company'), navigate: true);
            return false;
        }

        return true;
    }

    private function ensureEmissionRequirements(): bool
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Configura primero una empresa para emitir documentos.',
            ]);
            return false;
        }

        $checklist = $company->emissionReadinessChecklist();

        if (!$checklist['sri_password']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Antes de emitir, debes configurar la clave del SRI en Configuración > Empresa.',
            ]);
            $this->redirect(route('panel.settings.company'), navigate: true);
            return false;
        }

        if (!$checklist['digital_signature']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Antes de emitir, debes cargar una firma electrónica .p12 válida.',
            ]);
            $this->redirect(route('panel.settings.company'), navigate: true);
            return false;
        }

        return true;
    }

    private function save(DocumentStatus $status, bool $process = false): void
    {
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'emission_point_id' => 'required|exists:emission_points,id',
            'issue_date' => 'required|date',
        ];

        $messages = [
            'customer_id.required' => 'Seleccione un cliente.',
            'related_document_id.required' => 'Seleccione el documento de referencia.',
            'items.required' => 'Agregue al menos un producto.',
            'items.min' => 'Agregue al menos un producto.',
            'modification_reason.required' => 'Ingrese el motivo de la modificación.',
            'withholding_details.required' => 'Agregue al menos un detalle de retención.',
            'withholding_details.min' => 'Agregue al menos un detalle de retención.',
        ];

        // Reglas condicionales por tipo
        if ($this->needsItems) {
            $rules['items'] = 'required|array|min:1';
        }

        if ($this->needsRelatedDocument) {
            $rules['related_document_id'] = 'required|exists:electronic_documents,id';
            $rules['modification_reason'] = 'required|string|max:300';
        }

        if ($this->isRetention) {
            $rules['withholding_details'] = 'required|array|min:1';
        }

        $this->validate($rules, $messages);

        DB::transaction(function () use ($status, $process) {
            $tenantId = auth()->user()->tenant_id;
            $company = auth()->user()->tenant->companies()->first();
            $branch = Branch::find($this->branch_id);
            $emissionPoint = EmissionPoint::find($this->emission_point_id);

            $data = [
                'tenant_id' => $tenantId,
                'company_id' => $company->id,
                'branch_id' => $this->branch_id,
                'emission_point_id' => $this->emission_point_id,
                'customer_id' => $this->customer_id,
                'created_by' => auth()->id(),
                'document_type' => DocumentType::from($this->document_type),
                'environment' => $company->sri_environment,
                'series' => $branch->code . '-' . $emissionPoint->code,
                'status' => $status,
                'issue_date' => $this->issue_date,
                'due_date' => $this->due_date ?: null,
                'notes' => $this->notes ?: null,
                'subtotal_0' => $this->subtotal_0,
                'subtotal_5' => $this->subtotal_5,
                'subtotal_12' => $this->subtotal_12,
                'subtotal_15' => $this->subtotal_15,
                'subtotal_no_tax' => 0,
                'total_discount' => $this->total_discount,
                'total_tax' => $this->total_tax,
                'tip' => $this->tip,
                'total' => $this->total,
                'payment_methods' => $this->payment_methods ?: null,
                'currency' => 'DOLAR',
            ];

            // Datos de documento relacionado (NC/ND)
            if ($this->needsRelatedDocument && $this->related_document_id) {
                $relatedDoc = ElectronicDocument::find($this->related_document_id);
                $data['related_document_id'] = $this->related_document_id;
                $data['related_document_type'] = $relatedDoc->document_type->value;
                $data['additional_info'] = array_merge(
                    $data['additional_info'] ?? [],
                    [
                        'modification_reason' => $this->modification_reason,
                        'related_document_number' => $relatedDoc->getDocumentNumber(),
                        'related_document_date' => $relatedDoc->issue_date->format('d/m/Y'),
                    ]
                );
            }

            // Datos de guía de remisión
            if ($this->isGuide) {
                $data['additional_info'] = array_merge(
                    $data['additional_info'] ?? [],
                    [
                        'carrier_ruc' => $this->carrier_ruc,
                        'carrier_name' => $this->carrier_name,
                        'carrier_plate' => $this->carrier_plate,
                        'origin_address' => $this->origin_address,
                        'destination_address' => $this->destination_address,
                        'destination_ruc' => $this->destination_ruc,
                        'destination_name' => $this->destination_name,
                        'transport_start_date' => $this->transport_start_date,
                    ]
                );
            }

            if ($this->document) {
                $this->document->update($data);
                $document = $this->document;
                $document->items()->delete();
                if ($this->isRetention) {
                    $document->withholdingDetails()->delete();
                }
            } else {
                // Obtener secuencial
                $sequential = \App\Models\SRI\SequentialNumber::getNextNumber(
                    $this->emission_point_id,
                    DocumentType::from($this->document_type)
                );
                $data['sequential'] = \App\Models\SRI\SequentialNumber::formatNumber($sequential);
                $document = ElectronicDocument::create($data);
            }

            // Crear items (facturas, NC, ND)
            if ($this->needsItems) {
                foreach ($this->items as $item) {
                    DocumentItem::create([
                        'tenant_id' => $tenantId,
                        'electronic_document_id' => $document->id,
                        'product_id' => $item['product_id'],
                        'main_code' => $item['main_code'],
                        'aux_code' => null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $item['discount'],
                        'discount_percentage' => $item['discount_percent'],
                        'subtotal' => $item['subtotal'],
                        'tax_code' => '2', // IVA
                        'tax_percentage_code' => $item['tax_code'],
                        'tax_rate' => $this->getTaxRate($item['tax_code']),
                        'tax_value' => $item['tax_value'],
                        'total' => $item['total'],
                    ]);
                }
            }

            // Crear detalles de retención
            if ($this->isRetention) {
                foreach ($this->withholding_details as $detail) {
                    WithholdingDetail::create([
                        'tenant_id' => $tenantId,
                        'electronic_document_id' => $document->id,
                        'tax_type' => $detail['tax_type'],
                        'tax_code' => $detail['tax_code'],
                        'withholding_code' => $detail['withholding_code'],
                        'withholding_percentage' => $detail['withholding_percentage'],
                        'base_amount' => $detail['base_amount'],
                        'withheld_amount' => $detail['withheld_amount'],
                    ]);
                }
            }

            // Procesar si se solicitó
            if ($process) {
                ProcessDocumentJob::dispatch($document);
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $this->isEdit
                    ? 'Documento actualizado correctamente.'
                    : 'Documento creado correctamente.',
            ]);

            $this->redirect(route('panel.documents.show', $document), navigate: true);
        });
    }

    public function render()
    {
        return view('livewire.panel.documents.document-create', [
            'customers' => $this->customers,
            'products' => $this->products,
            'branches' => $this->branches,
            'emissionPoints' => $this->emissionPoints,
            'selectedCustomer' => $this->selectedCustomer,
            'relatedDocuments' => $this->needsRelatedDocument ? $this->relatedDocuments : collect(),
            'selectedRelatedDocument' => $this->selectedRelatedDocument,
        ])->layout('layouts.tenant', [
            'title' => $this->documentTitle,
        ]);
    }
}
