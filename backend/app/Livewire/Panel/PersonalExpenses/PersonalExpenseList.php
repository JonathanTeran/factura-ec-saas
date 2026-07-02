<?php

namespace App\Livewire\Panel\PersonalExpenses;

use App\Enums\PersonalExpenseCategory;
use App\Models\Tenant\PersonalExpense;
use Livewire\Component;
use Livewire\WithPagination;

class PersonalExpenseList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public int $fiscalYear;
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search'     => ['except' => ''],
        'category'   => ['except' => ''],
        'fiscalYear' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->fiscalYear = now()->year;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiscalYear(): void { $this->resetPage(); }

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
        $this->reset(['search', 'category']);
        $this->resetPage();
    }

    public function getExpensesProperty()
    {
        $query = PersonalExpense::where('tenant_id', auth()->user()->tenant_id)
            ->where('user_id', auth()->id())
            ->where('fiscal_year', $this->fiscalYear);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('issuer_name', 'like', "%{$this->search}%")
                    ->orWhere('issuer_ruc', 'like', "%{$this->search}%");
            });
        }

        if ($this->category) {
            $query->where('category', $this->category);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $baseQuery = PersonalExpense::where('tenant_id', auth()->user()->tenant_id)
            ->where('user_id', auth()->id())
            ->where('fiscal_year', $this->fiscalYear);

        $total = (float) $baseQuery->sum('amount');

        $byCategory = [];
        foreach (PersonalExpenseCategory::cases() as $cat) {
            $amount = (float) (clone $baseQuery)->where('category', $cat->value)->sum('amount');
            if ($amount > 0) {
                $byCategory[$cat->value] = ['label' => $cat->label(), 'amount' => $amount, 'color' => $cat->color()];
            }
        }

        arsort($byCategory);

        return [
            'total'       => $total,
            'count'       => (clone $baseQuery)->count(),
            'by_category' => array_slice($byCategory, 0, 5, true),
        ];
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

    public function delete(int $id): void
    {
        PersonalExpense::where('tenant_id', auth()->user()->tenant_id)
            ->where('user_id', auth()->id())
            ->findOrFail($id)
            ->delete();

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Gasto eliminado.']);
    }

    public function render()
    {
        return view('livewire.panel.personal-expenses.personal-expense-list', [
            'expenses'   => $this->expenses,
            'stats'      => $this->stats,
            'years'      => $this->years,
            'categories' => $this->categories,
        ])->layout('layouts.tenant', ['title' => 'Gastos Personales']);
    }
}
