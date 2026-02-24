<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\AccountingStandard;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Tenant\Company;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\FiscalPeriodService;
use Livewire\Component;

class AccountingSetupWizard extends Component
{
    public int $step = 1;
    public string $accountingStandard = 'niif_pymes';
    public int $year;
    public int $companyId = 0;

    public bool $accountsSeeded = false;
    public bool $periodsCreated = false;
    public int $accountsCount = 0;
    public int $periodsCount = 0;

    public function mount(): void
    {
        $this->year = now()->year;

        $companies = Company::where('tenant_id', auth()->user()->tenant_id)->get();
        if ($companies->count() === 1) {
            $this->companyId = $companies->first()->id;
        }

        if ($this->companyId) {
            $this->checkExistingSetup();
        }
    }

    public function updatedCompanyId(): void
    {
        $this->checkExistingSetup();
    }

    protected function checkExistingSetup(): void
    {
        if (!$this->companyId) {
            return;
        }

        $setting = AccountingSetting::where('company_id', $this->companyId)->first();
        if ($setting) {
            $this->accountingStandard = $setting->accounting_standard->value;
        }

        $this->accountsCount = Account::where('company_id', $this->companyId)->count();
        $this->accountsSeeded = $this->accountsCount > 0;

        $this->periodsCount = FiscalPeriod::where('company_id', $this->companyId)
            ->where('year', $this->year)
            ->count();
        $this->periodsCreated = $this->periodsCount > 0;
    }

    public function nextStep(): void
    {
        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function saveStandard(): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'accountingStandard' => 'required|in:niif_full,niif_pymes',
        ]);

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        AccountingSetting::updateOrCreate(
            [
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
            ],
            [
                'accounting_standard' => AccountingStandard::from($this->accountingStandard),
                'auto_journal_entries' => true,
            ]
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Norma contable configurada correctamente.',
        ]);

        $this->nextStep();
    }

    public function seedAccounts(ChartOfAccountsService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
        ]);

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        if (Account::where('company_id', $company->id)->exists()) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Ya existe un plan de cuentas para esta empresa.',
            ]);
            $this->accountsSeeded = true;
            $this->accountsCount = Account::where('company_id', $company->id)->count();
            $this->nextStep();
            return;
        }

        $standard = AccountingStandard::from($this->accountingStandard);

        $service->forCompany($company)->seedDefaultAccounts($standard);

        $this->accountsSeeded = true;
        $this->accountsCount = Account::where('company_id', $company->id)->count();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Plan de cuentas creado con {$this->accountsCount} cuentas.",
        ]);

        $this->nextStep();
    }

    public function createPeriods(FiscalPeriodService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $periods = $service->forCompany($company)->createPeriodsForYear($this->year);

        $this->periodsCreated = true;
        $this->periodsCount = count($periods);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Se crearon {$this->periodsCount} periodos fiscales para {$this->year}.",
        ]);
    }

    public function finish(): mixed
    {
        return $this->redirect(route('panel.accounting.dashboard'), navigate: true);
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.accounting-setup-wizard', [
            'companies' => $this->companies,
            'standards' => AccountingStandard::cases(),
        ])->layout('layouts.tenant', ['title' => 'Configuracion Contable']);
    }
}
