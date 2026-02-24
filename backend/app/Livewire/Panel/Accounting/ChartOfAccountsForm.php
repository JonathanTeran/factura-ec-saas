<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\AccountType;
use App\Models\Accounting\Account;
use App\Models\Tenant\Company;
use App\Services\Accounting\ChartOfAccountsService;
use Livewire\Component;

class ChartOfAccountsForm extends Component
{
    public ?int $accountId = null;
    public int $companyId = 0;
    public ?int $parentId = null;
    public string $code = '';
    public string $name = '';
    public string $accountType = 'activo';
    public string $accountNature = 'debit';
    public string $description = '';
    public string $taxFormCode = '';
    public bool $allowsMovement = true;
    public bool $isActive = true;
    public int $level = 1;

    public function mount(?int $account = null, ?int $parentId = null): void
    {
        $company = Company::where('tenant_id', auth()->user()->tenant_id)->first();
        if ($company) {
            $this->companyId = $company->id;
        }

        if ($parentId) {
            $this->parentId = $parentId;
            $parent = Account::findOrFail($parentId);
            $this->accountType = $parent->account_type->value;
            $this->accountNature = $parent->account_nature;
            $this->level = $parent->level + 1;
            $this->companyId = $parent->company_id;

            $lastChild = Account::where('parent_id', $parentId)
                ->orderByDesc('code')
                ->first();
            if ($lastChild) {
                $parts = explode('.', $lastChild->code);
                $lastPart = (int) end($parts);
                $parts[count($parts) - 1] = str_pad($lastPart + 1, 2, '0', STR_PAD_LEFT);
                $this->code = implode('.', $parts);
            } else {
                $this->code = $parent->code . '.01';
            }
        }

        if ($account) {
            $this->accountId = $account;
            $acc = Account::where('tenant_id', auth()->user()->tenant_id)
                ->findOrFail($account);

            $this->companyId = $acc->company_id;
            $this->parentId = $acc->parent_id;
            $this->code = $acc->code;
            $this->name = $acc->name;
            $this->accountType = $acc->account_type->value;
            $this->accountNature = $acc->account_nature;
            $this->description = $acc->description ?? '';
            $this->taxFormCode = $acc->tax_form_code ?? '';
            $this->allowsMovement = $acc->allows_movement;
            $this->isActive = $acc->is_active;
            $this->level = $acc->level;
        }
    }

    public function updatedAccountType(string $value): void
    {
        $type = AccountType::from($value);
        $this->accountNature = $type->defaultNature();
    }

    public function save(ChartOfAccountsService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'accountType' => 'required|in:activo,pasivo,patrimonio,ingreso,costo,gasto',
            'accountNature' => 'required|in:debit,credit',
        ]);

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->accountType,
            'account_nature' => $this->accountNature,
            'parent_id' => $this->parentId,
            'level' => $this->level,
            'allows_movement' => $this->allowsMovement,
            'is_active' => $this->isActive,
            'description' => $this->description ?: null,
            'tax_form_code' => $this->taxFormCode ?: null,
        ];

        if ($this->accountId) {
            $account = Account::where('company_id', $company->id)
                ->findOrFail($this->accountId);
            $service->forCompany($company)->updateAccount($account, $data);
            $message = 'Cuenta actualizada correctamente.';
        } else {
            $service->forCompany($company)->createAccount($data);
            $message = 'Cuenta creada correctamente.';
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        $this->redirect(route('panel.accounting.chart-of-accounts'), navigate: true);
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function getParentAccountsProperty()
    {
        if (!$this->companyId) {
            return collect();
        }

        $query = Account::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('code');

        if ($this->accountId) {
            $query->where('id', '!=', $this->accountId);
        }

        return $query->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.chart-of-accounts-form', [
            'companies' => $this->companies,
            'parentAccounts' => $this->parentAccounts,
            'accountTypes' => AccountType::cases(),
        ])->layout('layouts.tenant', [
            'title' => $this->accountId ? 'Editar Cuenta' : 'Nueva Cuenta',
        ]);
    }
}
