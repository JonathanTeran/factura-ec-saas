<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\AccountType;
use App\Models\Accounting\Account;
use App\Models\Tenant\Company;
use App\Services\Accounting\ChartOfAccountsService;
use Livewire\Component;

class ChartOfAccountsList extends Component
{
    public string $search = '';
    public string $accountType = '';
    public int $companyId = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'accountType' => ['except' => ''],
    ];

    public function mount(): void
    {
        $company = Company::where('tenant_id', auth()->user()->tenant_id)->first();
        if ($company) {
            $this->companyId = $company->id;
        }
    }

    public function updatingSearch(): void
    {
        // Reset any UI state when searching
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'accountType']);
    }

    public function deleteAccount(int $accountId, ChartOfAccountsService $service): void
    {
        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $account = Account::where('company_id', $company->id)
            ->findOrFail($accountId);

        try {
            $service->forCompany($company)->deleteAccount($account);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Cuenta eliminada correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function toggleActive(int $accountId): void
    {
        $account = Account::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($accountId);

        $account->update(['is_active' => !$account->is_active]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $account->is_active ? 'Cuenta activada.' : 'Cuenta desactivada.',
        ]);
    }

    public function getAccountsProperty()
    {
        if (!$this->companyId) {
            return collect();
        }

        if ($this->search || $this->accountType) {
            $query = Account::where('company_id', $this->companyId);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('code', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            }

            if ($this->accountType) {
                $query->where('account_type', $this->accountType);
            }

            return $query->orderBy('code')->get();
        }

        return Account::where('company_id', $this->companyId)
            ->whereNull('parent_id')
            ->with('children.children.children.children.children')
            ->orderBy('code')
            ->get();
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.chart-of-accounts-list', [
            'accounts' => $this->accounts,
            'companies' => $this->companies,
            'accountTypes' => AccountType::cases(),
        ])->layout('layouts.tenant', ['title' => 'Plan de Cuentas']);
    }
}
