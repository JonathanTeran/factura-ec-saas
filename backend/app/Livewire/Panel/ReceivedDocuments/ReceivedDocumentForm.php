<?php

namespace App\Livewire\Panel\ReceivedDocuments;

use App\Enums\ExpenseCategory;
use App\Models\Tenant\Company;
use App\Models\Tenant\ReceivedDocument;
use Livewire\Component;

class ReceivedDocumentForm extends Component
{
    public ?int $documentId = null;
    public int $companyId = 0;
    public string $documentType = '01';
    public string $accessKey = '';
    public string $authorizationNumber = '';
    public string $authorizationDate = '';
    public string $issuerRuc = '';
    public string $issuerName = '';
    public string $issueDate = '';
    public float $subtotal0 = 0;
    public float $subtotal5 = 0;
    public float $subtotal12 = 0;
    public float $subtotal15 = 0;
    public float $subtotalNoTax = 0;
    public float $totalDiscount = 0;
    public float $totalTax = 0;
    public float $total = 0;
    public string $expenseCategory = '';
    public string $notes = '';
    public bool $isProcessed = false;

    public function mount(?int $document = null): void
    {
        $this->issueDate = now()->format('Y-m-d');

        if ($document) {
            $this->documentId = $document;
            $doc = ReceivedDocument::where('tenant_id', auth()->user()->tenant_id)->findOrFail($document);

            $this->companyId           = $doc->company_id;
            $this->documentType        = $doc->document_type;
            $this->accessKey           = $doc->access_key ?? '';
            $this->authorizationNumber = $doc->authorization_number ?? '';
            $this->authorizationDate   = $doc->authorization_date?->format('Y-m-d') ?? '';
            $this->issuerRuc           = $doc->issuer_ruc;
            $this->issuerName          = $doc->issuer_name;
            $this->issueDate           = $doc->issue_date->format('Y-m-d');
            $this->subtotal0           = (float) $doc->subtotal_0;
            $this->subtotal5           = (float) $doc->subtotal_5;
            $this->subtotal12          = (float) $doc->subtotal_12;
            $this->subtotal15          = (float) $doc->subtotal_15;
            $this->subtotalNoTax       = (float) $doc->subtotal_no_tax;
            $this->totalDiscount       = (float) $doc->total_discount;
            $this->totalTax            = (float) $doc->total_tax;
            $this->total               = (float) $doc->total;
            $this->expenseCategory     = $doc->expense_category?->value ?? '';
            $this->notes               = $doc->notes ?? '';
            $this->isProcessed         = $doc->is_processed;
        }
    }

    public function updatedSubtotal0(): void { $this->recalculate(); }
    public function updatedSubtotal5(): void { $this->recalculate(); }
    public function updatedSubtotal12(): void { $this->recalculate(); }
    public function updatedSubtotal15(): void { $this->recalculate(); }
    public function updatedSubtotalNoTax(): void { $this->recalculate(); }
    public function updatedTotalDiscount(): void { $this->recalculate(); }

    public function recalculate(): void
    {
        $subtotal         = $this->subtotal0 + $this->subtotal5 + $this->subtotal12 + $this->subtotal15 + $this->subtotalNoTax;
        $this->totalTax   = round(
            ($this->subtotal5 * 0.05) + ($this->subtotal12 * 0.12) + ($this->subtotal15 * 0.15),
            2
        );
        $this->total = round($subtotal + $this->totalTax - $this->totalDiscount, 2);
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('business_name')->get();
    }

    public function getCategoriesProperty(): array
    {
        return ExpenseCategory::cases();
    }

    protected function rules(): array
    {
        return [
            'companyId'    => ['required', 'integer', 'min:1'],
            'documentType' => ['required', 'in:01,03,04,05,06,07'],
            'issuerRuc'    => ['required', 'string', 'max:13'],
            'issuerName'   => ['required', 'string', 'max:300'],
            'issueDate'    => ['required', 'date'],
        ];
    }

    protected function messages(): array
    {
        return [
            'companyId.required' => 'Selecciona una empresa.',
            'issuerRuc.required' => 'El RUC del emisor es obligatorio.',
            'issuerName.required' => 'La razón social del emisor es obligatoria.',
            'issueDate.required' => 'La fecha de emisión es obligatoria.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id'            => auth()->user()->tenant_id,
            'company_id'           => $this->companyId,
            'document_type'        => $this->documentType,
            'access_key'           => $this->accessKey ?: null,
            'authorization_number' => $this->authorizationNumber ?: null,
            'authorization_date'   => $this->authorizationDate ?: null,
            'issuer_ruc'           => $this->issuerRuc,
            'issuer_name'          => $this->issuerName,
            'issue_date'           => $this->issueDate,
            'subtotal_0'           => $this->subtotal0,
            'subtotal_5'           => $this->subtotal5,
            'subtotal_12'          => $this->subtotal12,
            'subtotal_15'          => $this->subtotal15,
            'subtotal_no_tax'      => $this->subtotalNoTax,
            'total_discount'       => $this->totalDiscount,
            'total_tax'            => $this->totalTax,
            'total'                => $this->total,
            'expense_category'     => $this->expenseCategory ?: null,
            'is_processed'         => $this->isProcessed,
            'notes'                => $this->notes ?: null,
            'created_by'           => auth()->id(),
        ];

        if ($this->documentId) {
            ReceivedDocument::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->documentId)->update($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Documento actualizado correctamente.']);
        } else {
            ReceivedDocument::create($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Documento registrado correctamente.']);
        }

        $this->redirect(route('panel.received-documents.index'));
    }

    public function render()
    {
        return view('livewire.panel.received-documents.received-document-form', [
            'companies'  => $this->companies,
            'categories' => $this->categories,
        ])->layout('layouts.tenant', ['title' => $this->documentId ? 'Editar Documento' : 'Registrar Documento Recibido']);
    }
}
