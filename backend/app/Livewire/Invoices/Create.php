<?php

namespace App\Livewire\Invoices;

use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Product;
use Livewire\Component;

class Create extends Component
{
    // Form state
    public ?int $company_id = null;
    public ?int $emission_point_id = null;
    public ?int $customer_id = null;
    public string $customer_search = '';
    public array $items = [];
    public string $payment_method = '01';
    public int $payment_term = 0;
    public array $additional_info = [];

    // Calculated totals
    public float $subtotal_0 = 0;
    public float $subtotal_5 = 0;
    public float $subtotal_12 = 0;
    public float $subtotal_15 = 0;
    public float $tax_12 = 0;
    public float $tax_15 = 0;
    public float $discount = 0;
    public float $total = 0;

    // UI state
    public bool $showCustomerModal = false;
    public bool $showProductSearch = false;
    public array $searchedProducts = [];
    public array $searchedCustomers = [];

    protected $listeners = ['productSelected', 'customerSelected'];

    public function mount(): void
    {
        // Set default company
        $user = auth()->user();
        $company = Company::where('tenant_id', $user->tenant_id)->first();

        if ($company) {
            $this->company_id = $company->id;
            $emissionPoint = $company->branches()->first()?->emissionPoints()->first();
            if ($emissionPoint) {
                $this->emission_point_id = $emissionPoint->id;
            }
        }

        // Add one empty item
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'main_code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'tax_rate' => 12,
            'subtotal' => 0,
            'tax_base' => 0,
            'tax_value' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }

    public function searchProducts(string $query): void
    {
        if (strlen($query) < 2) {
            $this->searchedProducts = [];
            return;
        }

        $this->searchedProducts = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function selectProduct(int $index, int $productId): void
    {
        $product = Product::find($productId);

        if ($product && $product->tenant_id === auth()->user()->tenant_id) {
            $this->items[$index]['product_id'] = $product->id;
            $this->items[$index]['main_code'] = $product->code;
            $this->items[$index]['description'] = $product->name;
            $this->items[$index]['unit_price'] = (float) $product->unit_price;
            $this->items[$index]['tax_rate'] = (float) ($product->tax_rate ?? 12);
            $this->calculateItemTotals($index);
        }

        $this->searchedProducts = [];
    }

    public function searchCustomers(string $query): void
    {
        if (strlen($query) < 2) {
            $this->searchedCustomers = [];
            return;
        }

        $this->searchedCustomers = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('identification_number', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::find($customerId);

        if ($customer && $customer->tenant_id === auth()->user()->tenant_id) {
            $this->customer_id = $customer->id;
            $this->customer_search = $customer->name . ' - ' . $customer->identification_number;
        }

        $this->searchedCustomers = [];
    }

    public function updatedItems(): void
    {
        $this->calculateTotals();
    }

    public function calculateItemTotals(int $index): void
    {
        $item = &$this->items[$index];

        $subtotal = ($item['quantity'] * $item['unit_price']) - $item['discount'];
        $item['subtotal'] = round($subtotal, 2);
        $item['tax_base'] = $item['subtotal'];
        $item['tax_value'] = round($item['subtotal'] * ($item['tax_rate'] / 100), 2);

        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $this->subtotal_0 = 0;
        $this->subtotal_5 = 0;
        $this->subtotal_12 = 0;
        $this->subtotal_15 = 0;
        $this->tax_12 = 0;
        $this->tax_15 = 0;
        $this->discount = 0;
        $tax_5 = 0;

        foreach ($this->items as &$item) {
            $subtotal = ($item['quantity'] * $item['unit_price']) - $item['discount'];
            $item['subtotal'] = round($subtotal, 2);
            $item['tax_base'] = $item['subtotal'];
            $item['tax_value'] = round($item['subtotal'] * ($item['tax_rate'] / 100), 2);

            $this->discount += $item['discount'];

            switch ((int) $item['tax_rate']) {
                case 0:
                    $this->subtotal_0 += $item['subtotal'];
                    break;
                case 5:
                    $this->subtotal_5 += $item['subtotal'];
                    $tax_5 += $item['tax_value'];
                    break;
                case 12:
                    $this->subtotal_12 += $item['subtotal'];
                    $this->tax_12 += $item['tax_value'];
                    break;
                case 15:
                    $this->subtotal_15 += $item['subtotal'];
                    $this->tax_15 += $item['tax_value'];
                    break;
            }
        }

        $this->total = $this->subtotal_0 + $this->subtotal_5 + $this->subtotal_12 + $this->subtotal_15 + $tax_5 + $this->tax_12 + $this->tax_15;
    }

    protected function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'emission_point_id' => ['required', 'exists:emission_points,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.main_code' => ['required', 'string'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string'],
        ];
    }

    public function save(bool $sendToSRI = false): void
    {
        $this->validate();

        $tenant = auth()->user()->tenant;

        // Check plan limits
        if (!$tenant->canIssueDocuments()) {
            session()->flash('error', 'Has alcanzado el límite de documentos de tu plan.');
            return;
        }

        $company = Company::findOrFail($this->company_id);
        $emissionPoint = EmissionPoint::findOrFail($this->emission_point_id);
        $sequential = $emissionPoint->getNextSequential(DocumentType::FACTURA);

        // Create document
        $series = $emissionPoint->branch->code . '-' . $emissionPoint->code;
        $document = ElectronicDocument::create([
            'tenant_id' => $tenant->id,
            'company_id' => $this->company_id,
            'branch_id' => $emissionPoint->branch_id,
            'emission_point_id' => $emissionPoint->id,
            'customer_id' => $this->customer_id,
            'document_type' => DocumentType::FACTURA,
            'environment' => $company->sri_environment,
            'series' => $series,
            'sequential' => str_pad((string) $sequential, 9, '0', STR_PAD_LEFT),
            'issue_date' => now(),
            'currency' => 'DOLAR',
            'subtotal_no_tax' => 0,
            'subtotal_0' => $this->subtotal_0,
            'subtotal_5' => $this->subtotal_5,
            'subtotal_12' => $this->subtotal_12,
            'subtotal_15' => $this->subtotal_15,
            'total_discount' => $this->discount,
            'total_tax' => ($this->tax_12 ?? 0) + ($this->tax_15 ?? 0),
            'tip' => 0,
            'total' => $this->total,
            'payment_methods' => [[
                'code' => (string) $this->payment_method,
                'amount' => (float) $this->total,
                'term' => (int) ($this->payment_term ?? 0),
                'time_unit' => 'dias',
            ]],
            'status' => DocumentStatus::DRAFT,
            'additional_info' => $this->additional_info,
            'created_by' => auth()->id(),
        ]);

        // Create items
        foreach ($this->items as $item) {
            $document->items()->create([
                'product_id' => $item['product_id'],
                'main_code' => $item['main_code'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'],
                'subtotal' => $item['subtotal'],
                'tax_code' => '2',
                'tax_percentage_code' => $item['tax_rate'] == 0 ? '0' : ($item['tax_rate'] == 12 ? '2' : '4'),
                'tax_rate' => $item['tax_rate'],
                'tax_base' => $item['tax_base'],
                'tax_value' => $item['tax_value'],
            ]);
        }

        // Increment tenant counter
        $tenant->incrementDocumentCount();

        // Send to SRI if requested
        if ($sendToSRI && $company->hasValidSignature()) {
            $document->update(['status' => DocumentStatus::PROCESSING]);
            ProcessDocumentJob::dispatch($document);
            session()->flash('success', 'Factura creada y enviada al SRI.');
        } else {
            session()->flash('success', 'Factura guardada como borrador.');
        }

        $this->redirect(route('tenant.documents.show', $document));
    }

    public function saveAsDraft(): void
    {
        $this->save(false);
    }

    public function saveAndSend(): void
    {
        $this->save(true);
    }

    public function render()
    {
        $companies = Company::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->with('branches.emissionPoints')
            ->get();

        $emissionPoints = [];
        if ($this->company_id) {
            $company = $companies->firstWhere('id', $this->company_id);
            if ($company) {
                foreach ($company->branches as $branch) {
                    foreach ($branch->emissionPoints as $ep) {
                        $emissionPoints[] = [
                            'id' => $ep->id,
                            'label' => "{$branch->code}-{$ep->code} ({$branch->name})",
                        ];
                    }
                }
            }
        }

        $paymentMethods = [
            '01' => 'Sin utilización del sistema financiero',
            '15' => 'Compensación de deudas',
            '16' => 'Tarjeta de débito',
            '17' => 'Dinero electrónico',
            '18' => 'Tarjeta prepago',
            '19' => 'Tarjeta de crédito',
            '20' => 'Otros con utilización del sistema financiero',
            '21' => 'Endoso de títulos',
        ];

        return view('livewire.invoices.create', [
            'companies' => $companies,
            'emissionPoints' => $emissionPoints,
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.tenant', ['title' => 'Nueva Factura']);
    }
}
