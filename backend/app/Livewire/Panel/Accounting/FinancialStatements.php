<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Tenant\Company;
use App\Services\Accounting\FinancialReportService;
use Livewire\Component;

class FinancialStatements extends Component
{
    public int $companyId = 0;
    public string $reportType = 'balance_sheet';
    public string $dateFrom = '';
    public string $dateTo = '';

    protected $queryString = [
        'reportType' => ['except' => 'balance_sheet'],
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

    public function updatedReportType(): void
    {
        // Reactive - report recalculates automatically
    }

    public function getReportProperty(): ?array
    {
        if (!$this->companyId) {
            return null;
        }

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $service = app(FinancialReportService::class)->forCompany($company);

        return match ($this->reportType) {
            'balance_sheet' => $service->getBalanceSheet($this->dateTo ?: null),
            'income_statement' => $service->getIncomeStatement(
                $this->dateFrom ?: null,
                $this->dateTo ?: null
            ),
            'cash_flow' => $service->getCashFlowStatement(
                $this->dateFrom ?: null,
                $this->dateTo ?: null
            ),
            default => null,
        };
    }

    public function getReportTitleProperty(): string
    {
        return match ($this->reportType) {
            'balance_sheet' => 'Estado de Situacion Financiera (Balance General)',
            'income_statement' => 'Estado de Resultados Integral',
            'cash_flow' => 'Estado de Flujo de Efectivo',
            default => 'Reporte Financiero',
        };
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.financial-statements', [
            'report' => $this->report,
            'reportTitle' => $this->reportTitle,
            'companies' => $this->companies,
        ])->layout('layouts.tenant', ['title' => 'Estados Financieros']);
    }
}
