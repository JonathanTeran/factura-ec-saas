<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PersonalExpenseCategory;
use App\Models\Tenant\PersonalExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Gastos personales
 */
class PersonalExpenseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PersonalExpense::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id);

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->input('fiscal_year'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $expenses = $query->orderByDesc('issue_date')
            ->paginate($request->input('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => $expenses->items(),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year' => ['required', 'integer', 'min:2020'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'issuer_ruc' => ['nullable', 'string', 'max:13'],
            'issuer_name' => ['nullable', 'string', 'max:300'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense = PersonalExpense::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return $this->created(['expense' => $expense], 'Gasto registrado');
    }

    public function show(Request $request, PersonalExpense $personalExpense): JsonResponse
    {
        abort_if(
            $personalExpense->tenant_id !== $request->user()->tenant_id ||
            $personalExpense->user_id !== $request->user()->id,
            403,
        );
        return $this->success(['expense' => $personalExpense]);
    }

    public function update(Request $request, PersonalExpense $personalExpense): JsonResponse
    {
        abort_if(
            $personalExpense->tenant_id !== $request->user()->tenant_id ||
            $personalExpense->user_id !== $request->user()->id,
            403,
        );
        $validated = $request->validate([
            'category' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'string', 'max:500'],
            'issuer_ruc' => ['sometimes', 'nullable', 'string', 'max:13'],
            'issuer_name' => ['sometimes', 'nullable', 'string', 'max:300'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'issue_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $personalExpense->update($validated);
        return $this->success(['expense' => $personalExpense->fresh()], 'Gasto actualizado');
    }

    public function destroy(Request $request, PersonalExpense $personalExpense): JsonResponse
    {
        abort_if(
            $personalExpense->tenant_id !== $request->user()->tenant_id ||
            $personalExpense->user_id !== $request->user()->id,
            403,
        );
        $personalExpense->delete();
        return $this->success(null, 'Gasto eliminado');
    }

    public function summary(Request $request): JsonResponse
    {
        $year = $request->input('fiscal_year', now()->year);

        $expenses = PersonalExpense::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('fiscal_year', $year)
            ->get();

        $byCategory = $expenses->groupBy(fn ($e) => $e->category?->value ?? $e->category)
            ->map(fn ($group, $cat) => [
                'category' => $cat,
                'count' => $group->count(),
                'total' => round($group->sum('amount'), 2),
            ])
            ->values();

        return $this->success([
            'fiscal_year' => (int) $year,
            'total' => round($expenses->sum('amount'), 2),
            'count' => $expenses->count(),
            'by_category' => $byCategory,
        ]);
    }

    /**
     * GET personal-expenses-budget?year=YYYY&month=M
     * Monthly deductible budget per category vs. actual spending.
     */
    public function budget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $tenant = $request->user()->tenant;
        $stored = $tenant->settings['deductible_budget'] ?? [];

        $sums = PersonalExpense::where('tenant_id', $tenant->id)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $budgets = [];
        $spent = [];

        foreach (PersonalExpenseCategory::cases() as $category) {
            $budgets[$category->value] = round((float) ($stored[$category->value] ?? 0), 2);
            $spent[$category->value] = round((float) ($sums[$category->value] ?? 0), 2);
        }

        return $this->success([
            'budgets' => $budgets,
            'spent' => $spent,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * PUT personal-expenses-budget
     * Persists the monthly budget map under tenant settings['deductible_budget']
     * without clobbering other settings keys.
     */
    public function updateBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'budgets' => ['required', 'array'],
            'budgets.*' => ['numeric', 'min:0'],
        ]);

        $validCategories = array_column(PersonalExpenseCategory::cases(), 'value');
        $invalid = array_diff(array_keys($validated['budgets']), $validCategories);

        if ($invalid !== []) {
            return $this->validationError([
                'budgets' => ['Categorías inválidas: '.implode(', ', $invalid)],
            ], 'Categorías inválidas');
        }

        $budgets = [];
        foreach (PersonalExpenseCategory::cases() as $category) {
            $budgets[$category->value] = round((float) ($validated['budgets'][$category->value] ?? 0), 2);
        }

        $tenant = $request->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['deductible_budget'] = $budgets;
        $tenant->settings = $settings;
        $tenant->save();

        return $this->success(['budgets' => $budgets], 'Presupuesto guardado');
    }
}
