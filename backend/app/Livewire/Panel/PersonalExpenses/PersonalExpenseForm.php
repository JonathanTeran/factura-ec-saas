<?php

namespace App\Livewire\Panel\PersonalExpenses;

use App\Enums\PersonalExpenseCategory;
use App\Models\Tenant\PersonalExpense;
use Livewire\Component;

class PersonalExpenseForm extends Component
{
    public ?int $expenseId = null;
    public int $fiscalYear;
    public string $category = 'health';
    public string $description = '';
    public string $issuerRuc = '';
    public string $issuerName = '';
    public string $documentNumber = '';
    public string $issueDate = '';
    public float $amount = 0;
    public string $notes = '';

    public function mount(?int $expense = null): void
    {
        $this->fiscalYear = now()->year;
        $this->issueDate  = now()->format('Y-m-d');

        if ($expense) {
            $this->expenseId = $expense;
            $e = PersonalExpense::where('tenant_id', auth()->user()->tenant_id)
                ->where('user_id', auth()->id())
                ->findOrFail($expense);

            $this->fiscalYear     = $e->fiscal_year;
            $this->category       = $e->category->value;
            $this->description    = $e->description;
            $this->issuerRuc      = $e->issuer_ruc ?? '';
            $this->issuerName     = $e->issuer_name ?? '';
            $this->documentNumber = $e->document_number ?? '';
            $this->issueDate      = $e->issue_date->format('Y-m-d');
            $this->amount         = (float) $e->amount;
            $this->notes          = $e->notes ?? '';
        }
    }

    public function getYearsProperty(): array
    {
        $current = now()->year;
        return range($current, $current - 4);
    }

    public function getCategoriesProperty(): array
    {
        return PersonalExpenseCategory::cases();
    }

    protected function rules(): array
    {
        return [
            'fiscalYear'  => ['required', 'integer', 'min:2000'],
            'category'    => ['required', 'string'],
            'description' => ['required', 'string', 'max:300'],
            'issueDate'   => ['required', 'date'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function messages(): array
    {
        return [
            'description.required' => 'La descripción es obligatoria.',
            'issueDate.required'   => 'La fecha del comprobante es obligatoria.',
            'amount.min'           => 'El monto debe ser mayor a 0.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id'       => auth()->user()->tenant_id,
            'user_id'         => auth()->id(),
            'fiscal_year'     => $this->fiscalYear,
            'category'        => $this->category,
            'description'     => $this->description,
            'issuer_ruc'      => $this->issuerRuc ?: null,
            'issuer_name'     => $this->issuerName ?: null,
            'document_number' => $this->documentNumber ?: null,
            'issue_date'      => $this->issueDate,
            'amount'          => $this->amount,
            'notes'           => $this->notes ?: null,
        ];

        if ($this->expenseId) {
            PersonalExpense::where('tenant_id', auth()->user()->tenant_id)
                ->where('user_id', auth()->id())
                ->findOrFail($this->expenseId)
                ->update($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Gasto actualizado correctamente.']);
        } else {
            PersonalExpense::create($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Gasto registrado correctamente.']);
        }

        $this->redirect(route('panel.personal-expenses.index'));
    }

    public function render()
    {
        return view('livewire.panel.personal-expenses.personal-expense-form', [
            'years'      => $this->years,
            'categories' => $this->categories,
        ])->layout('layouts.tenant', ['title' => $this->expenseId ? 'Editar Gasto Personal' : 'Nuevo Gasto Personal']);
    }
}
