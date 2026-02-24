<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\BudgetStatus;
use App\Models\Accounting\Budget;
use App\Services\Accounting\BudgetService;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $year = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'year' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'year']);
        $this->resetPage();
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getBudgetsProperty()
    {
        $company = $this->company;

        if (!$company) {
            return Budget::where('id', 0)->paginate(15);
        }

        $query = Budget::where('company_id', $company->id)
            ->with(['createdByUser', 'approvedByUser']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->year) {
            $query->where('year', $this->year);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getAvailableYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--) {
            $years[] = $y;
        }
        return $years;
    }

    public function approveBudget(int $budgetId): void
    {
        $budget = Budget::findOrFail($budgetId);

        try {
            $service = app(BudgetService::class)->forCompany($this->company);
            $service->approveBudget($budget);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Presupuesto aprobado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function activateBudget(int $budgetId): void
    {
        $budget = Budget::findOrFail($budgetId);

        try {
            $service = app(BudgetService::class)->forCompany($this->company);
            $service->activateBudget($budget);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Presupuesto activado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function closeBudget(int $budgetId): void
    {
        $budget = Budget::findOrFail($budgetId);

        try {
            $service = app(BudgetService::class)->forCompany($this->company);
            $service->closeBudget($budget);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Presupuesto cerrado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.budget-list', [
            'budgets' => $this->budgets,
            'availableYears' => $this->availableYears,
            'statuses' => BudgetStatus::cases(),
        ])->layout('layouts.tenant', ['title' => 'Presupuestos']);
    }
}
