<?php

namespace App\Livewire\Panel\Purchases;

use App\Enums\IdentificationType;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Supplier;
use App\Services\Purchase\PurchaseService;
use Livewire\Component;
use Livewire\WithFileUploads;

class PurchaseForm extends Component
{
    use WithFileUploads;

    public ?int $purchaseId = null;
    public int $companyId = 0;
    public int $supplierId = 0;
    public string $documentType = '01';
    public string $supplierDocumentNumber = '';
    public string $supplierAuthorization = '';
    public string $issueDate = '';
    public string $authorizationDate = '';
    public ?string $notes = null;
    public $attachment = null;
    public array $items = [];

    // Supplier form
    public bool $showSupplierForm = false;
    public string $newSupplierType = '04';
    public string $newSupplierIdentification = '';
    public string $newSupplierName = '';
    public string $newSupplierEmail = '';

    public function mount(?int $purchase = null): void
    {
        $this->issueDate = now()->format('Y-m-d');

        if ($purchase) {
            $this->purchaseId = $purchase;
            $p = Purchase::where('tenant_id', auth()->user()->tenant_id)
                ->with('items')
                ->findOrFail($purchase);

            $this->companyId = $p->company_id;
            $this->supplierId = $p->supplier_id;
            $this->documentType = $p->document_type;
            $this->supplierDocumentNumber = $p->supplier_document_number;
            $this->supplierAuthorization = $p->supplier_authorization ?? '';
            $this->issueDate = $p->issue_date->format('Y-m-d');
            $this->authorizationDate = $p->authorization_date?->format('Y-m-d') ?? '';
            $this->notes = $p->notes;

            $this->items = $p->items->map(fn($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax_rate' => (float) $item->tax_rate,
                'product_id' => $item->product_id,
            ])->toArray();
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'tax_rate' => 15,
            'product_id' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function createSupplier(): void
    {
        $this->validate([
            'newSupplierType' => 'required',
            'newSupplierIdentification' => 'required|string|max:20',
            'newSupplierName' => 'required|string|max:255',
        ]);

        $supplier = Supplier::create([
            'tenant_id' => auth()->user()->tenant_id,
            'identification_type' => $this->newSupplierType,
            'identification' => $this->newSupplierIdentification,
            'business_name' => $this->newSupplierName,
            'email' => $this->newSupplierEmail ?: null,
        ]);

        $this->supplierId = $supplier->id;
        $this->showSupplierForm = false;
        $this->reset(['newSupplierType', 'newSupplierIdentification', 'newSupplierName', 'newSupplierEmail']);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Proveedor creado.']);
    }

    public function save(PurchaseService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'supplierId' => 'required|exists:suppliers,id',
            'documentType' => 'required|string|max:2',
            'supplierDocumentNumber' => 'required|string|max:17',
            'issueDate' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.000001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $data = [
            'company_id' => $this->companyId,
            'supplier_id' => $this->supplierId,
            'document_type' => $this->documentType,
            'supplier_document_number' => $this->supplierDocumentNumber,
            'supplier_authorization' => $this->supplierAuthorization ?: null,
            'issue_date' => $this->issueDate,
            'authorization_date' => $this->authorizationDate ?: null,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ];

        $tenant = auth()->user()->tenant;

        if ($this->purchaseId) {
            $purchase = Purchase::where('tenant_id', $tenant->id)->findOrFail($this->purchaseId);
            $service->forTenant($tenant)->updatePurchase($purchase, $data, $this->items);
            $message = 'Compra actualizada exitosamente.';
        } else {
            $service->forTenant($tenant)->createPurchase($data, $this->items);
            $message = 'Compra registrada exitosamente.';
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        $this->redirect(route('panel.purchases.index'), navigate: true);
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.purchases.purchase-form', [
            'companies' => $this->companies,
            'suppliers' => $this->suppliers,
            'identificationTypes' => IdentificationType::cases(),
        ])->layout('layouts.tenant', [
            'title' => $this->purchaseId ? 'Editar Compra' : 'Registrar Compra',
        ]);
    }
}
