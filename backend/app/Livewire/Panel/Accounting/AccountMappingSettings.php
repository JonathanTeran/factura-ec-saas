<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountMappingTemplate;
use Livewire\Component;

class AccountMappingSettings extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $document_type = '';
    public string $template_name = '';
    public bool $is_active = true;
    public array $rules_data = [];

    public function getDocumentTypesProperty(): array
    {
        return [
            'invoice' => 'Factura de Venta',
            'credit_note' => 'Nota de Credito',
            'debit_note' => 'Nota de Debito',
            'purchase' => 'Compra',
            'withholding' => 'Retencion',
            'settlement' => 'Liquidacion de Compra',
        ];
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getTemplatesProperty()
    {
        $company = $this->company;

        if (!$company) {
            return collect();
        }

        return AccountMappingTemplate::where('company_id', $company->id)
            ->orderBy('document_type')
            ->orderBy('name')
            ->get();
    }

    public function getAccountsProperty()
    {
        $company = $this->company;

        if (!$company) {
            return collect();
        }

        return Account::where('company_id', $company->id)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();

        if ($id) {
            $template = AccountMappingTemplate::findOrFail($id);
            $this->editingId = $template->id;
            $this->document_type = $template->document_type;
            $this->template_name = $template->name;
            $this->is_active = $template->is_active;
            $this->rules_data = $template->mapping_rules ?? [];
        }

        if (empty($this->rules_data)) {
            $this->addRule();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->document_type = '';
        $this->template_name = '';
        $this->is_active = true;
        $this->rules_data = [];
        $this->resetValidation();
    }

    public function addRule(): void
    {
        $this->rules_data[] = [
            'account_code' => '',
            'side' => 'debit',
            'amount_field' => 'subtotal',
            'description' => '',
        ];
    }

    public function removeRule(int $index): void
    {
        if (count($this->rules_data) <= 1) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Debe haber al menos una regla.',
            ]);
            return;
        }

        unset($this->rules_data[$index]);
        $this->rules_data = array_values($this->rules_data);
    }

    public function save(): void
    {
        $this->validate([
            'document_type' => 'required|string|max:50',
            'template_name' => 'required|string|max:200',
            'is_active' => 'boolean',
            'rules_data' => 'required|array|min:1',
            'rules_data.*.account_code' => 'required|string',
            'rules_data.*.side' => 'required|in:debit,credit',
            'rules_data.*.amount_field' => 'required|string|max:100',
            'rules_data.*.description' => 'nullable|string|max:200',
        ], [
            'document_type.required' => 'Selecciona el tipo de documento.',
            'template_name.required' => 'Ingresa un nombre para la plantilla.',
            'rules_data.required' => 'Agrega al menos una regla.',
            'rules_data.*.account_code.required' => 'Selecciona una cuenta.',
            'rules_data.*.side.required' => 'Selecciona debe o haber.',
            'rules_data.*.amount_field.required' => 'Selecciona el campo de monto.',
        ]);

        $company = $this->company;

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        $data = [
            'tenant_id' => auth()->user()->tenant_id,
            'company_id' => $company->id,
            'document_type' => $this->document_type,
            'name' => $this->template_name,
            'is_active' => $this->is_active,
            'mapping_rules' => $this->rules_data,
        ];

        if ($this->editingId) {
            $template = AccountMappingTemplate::findOrFail($this->editingId);
            $template->update($data);
            $message = 'Plantilla actualizada correctamente.';
        } else {
            AccountMappingTemplate::create($data);
            $message = 'Plantilla creada correctamente.';
        }

        $this->closeForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function toggleActive(int $id): void
    {
        $template = AccountMappingTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $template->is_active ? 'Plantilla activada.' : 'Plantilla desactivada.',
        ]);
    }

    public function delete(int $id): void
    {
        $template = AccountMappingTemplate::findOrFail($id);
        $template->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Plantilla eliminada.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.accounting.account-mapping-settings', [
            'templates' => $this->templates,
            'accounts' => $this->accounts,
            'documentTypes' => $this->documentTypes,
        ])->layout('layouts.tenant', ['title' => 'Plantillas de Contabilizacion']);
    }
}
