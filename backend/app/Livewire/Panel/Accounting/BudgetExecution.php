<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\BudgetStatus;
use App\Models\Accounting\Budget;
use App\Services\Accounting\BudgetService;
use Livewire\Component;

class BudgetExecution extends Component
{
    public ?int $budgetId = null;

    protected $queryString = [
        'budgetId' => ['except' => '', 'as' => 'budget'],
    ];

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getBudgetsProperty()
    {
        $company = $this->company;

        if (!$company) {
            return collect();
        }

        return Budget::where('company_id', $company->id)
            ->whereIn('status', [BudgetStatus::ACTIVE, BudgetStatus::APPROVED, BudgetStatus::CLOSED])
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getSelectedBudgetProperty(): ?Budget
    {
        if (!$this->budgetId) {
            return null;
        }

        return Budget::with('lines.account')->find($this->budgetId);
    }

    public function getExecutionDataProperty()
    {
        $budget = $this->selectedBudget;

        if (!$budget) {
            return collect();
        }

        $company = $this->company;

        if (!$company) {
            return collect();
        }

        $service = app(BudgetService::class)->forCompany($company);
        return $service->getBudgetExecution($budget);
    }

    public function getSummaryProperty(): array
    {
        $data = $this->executionData;

        if ($data->isEmpty()) {
            return [
                'total_budgeted' => 0,
                'total_executed' => 0,
                'percentage' => 0,
            ];
        }

        $totalBudgeted = $data->sum('total_budgeted');
        $totalExecuted = $data->sum('total_executed');

        return [
            'total_budgeted' => $totalBudgeted,
            'total_executed' => $totalExecuted,
            'percentage' => $totalBudgeted > 0 ? round(($totalExecuted / $totalBudgeted) * 100, 2) : 0,
        ];
    }

    public function getMonthsProperty(): array
    {
        return [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
    }

    public function render()
    {
        return view('livewire.panel.accounting.budget-execution', [
            'budgets' => $this->budgets,
            'selectedBudget' => $this->selectedBudget,
            'executionData' => $this->executionData,
            'summary' => $this->summary,
            'months' => $this->months,
        ])->layout('layouts.tenant', ['title' => 'Ejecucion Presupuestaria']);
    }
}
