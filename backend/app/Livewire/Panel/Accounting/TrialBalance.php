<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Tenant\Company;
use App\Services\Accounting\FinancialReportService;
use Livewire\Component;

class TrialBalance extends Component
{
    public int $companyId = 0;
    public string $dateFrom = '';
    public string $dateTo = '';

    protected $queryString = [
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        $company = Company::where('tenant_id', auth()->user()->tenant_id)->first();
        if ($company) {
            $this->companyId = $company->id;
        }

        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getTrialBalanceProperty(): array
    {
        if (!$this->companyId) {
            return ['data' => [], 'totals' => ['debit' => 0, 'credit' => 0]];
        }

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $service = app(FinancialReportService::class);

        return $service->forCompany($company)->getTrialBalance(
            $this->dateFrom ?: null,
            $this->dateTo ?: null
        );
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.trial-balance', [
            'trialBalance' => $this->trialBalance,
            'companies' => $this->companies,
        ])->layout('layouts.tenant', ['title' => 'Balance de Comprobacion']);
    }
}
