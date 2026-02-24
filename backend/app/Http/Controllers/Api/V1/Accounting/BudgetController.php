<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\Budget;
use App\Services\Accounting\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends ApiController
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Budget::where('company_id', $request->user()->current_company_id ?? 0)
            ->withCount('lines')
            ->orderByDesc('year');

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $budgets = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $budgets->items(),
            'meta' => [
                'current_page' => $budgets->currentPage(),
                'last_page' => $budgets->lastPage(),
                'per_page' => $budgets->perPage(),
                'total' => $budgets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'lines.*.month' => ['required', 'integer', 'min:1', 'max:12'],
            'lines.*.budgeted_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $company = $request->user()->currentCompany;

        $budget = $this->budgetService
            ->forCompany($company)
            ->createBudget(
                collect($validated)->except('lines')->toArray(),
                $validated['lines']
            );

        return $this->created(['budget' => $budget], 'Presupuesto creado exitosamente');
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        return $this->success([
            'budget' => $budget->load(['lines.account', 'lines.costCenter', 'createdByUser', 'approvedByUser']),
        ]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'year' => ['sometimes', 'integer', 'min:2020'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'lines.*.month' => ['required', 'integer', 'min:1', 'max:12'],
            'lines.*.budgeted_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.executed_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $company = $request->user()->currentCompany;

        try {
            $budget = $this->budgetService
                ->forCompany($company)
                ->updateBudget(
                    $budget,
                    collect($validated)->except('lines')->toArray(),
                    $validated['lines']
                );

            return $this->success(['budget' => $budget], 'Presupuesto actualizado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        if ($budget->status->value !== 'draft') {
            return $this->error('Solo se pueden eliminar presupuestos en borrador.');
        }

        $budget->delete();

        return $this->success(null, 'Presupuesto eliminado exitosamente');
    }

    public function approve(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);
        $company = $request->user()->currentCompany;

        try {
            $budget = $this->budgetService
                ->forCompany($company)
                ->approveBudget($budget);

            return $this->success(['budget' => $budget], 'Presupuesto aprobado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function activate(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);
        $company = $request->user()->currentCompany;

        try {
            $budget = $this->budgetService
                ->forCompany($company)
                ->activateBudget($budget);

            return $this->success(['budget' => $budget], 'Presupuesto activado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function close(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);
        $company = $request->user()->currentCompany;

        $budget = $this->budgetService
            ->forCompany($company)
            ->closeBudget($budget);

        return $this->success(['budget' => $budget], 'Presupuesto cerrado exitosamente');
    }

    protected function authorizeBudget(Request $request, Budget $budget): void
    {
        if ($budget->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este presupuesto.');
        }
    }
}
