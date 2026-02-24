<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\Account;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingAccountController extends ApiController
{
    public function __construct(
        private readonly ChartOfAccountsService $chartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Account::where('company_id', $request->user()->current_company_id ?? 0)
            ->orderBy('code');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('account_type', $request->input('type'));
        }

        if ($request->boolean('detail_only')) {
            $query->where('allows_movement', true);
        }

        if ($request->boolean('tree')) {
            $accounts = $query->whereNull('parent_id')
                ->with('children.children.children.children')
                ->get();

            return $this->success(['accounts' => $accounts]);
        }

        $accounts = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string', 'in:activo,pasivo,patrimonio,ingreso,costo,gasto'],
            'account_nature' => ['required', 'string', 'in:debit,credit'],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'allows_movement' => ['boolean'],
            'tax_form_code' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
        ]);

        $company = $request->user()->currentCompany;

        $account = $this->chartService
            ->forCompany($company)
            ->createAccount($validated);

        return $this->created(['account' => $account], 'Cuenta creada exitosamente');
    }

    public function show(Request $request, Account $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        return $this->success([
            'account' => $account->load(['parent', 'children']),
        ]);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'allows_movement' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'tax_form_code' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
        ]);

        $company = $request->user()->currentCompany;

        $account = $this->chartService
            ->forCompany($company)
            ->updateAccount($account, $validated);

        return $this->success(['account' => $account], 'Cuenta actualizada exitosamente');
    }

    public function destroy(Request $request, Account $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $company = $request->user()->currentCompany;
            $this->chartService->forCompany($company)->deleteAccount($account);
            return $this->success(null, 'Cuenta eliminada exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function authorizeAccount(Request $request, Account $account): void
    {
        if ($account->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta cuenta.');
        }
    }
}
