<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\Purchase;
use App\Services\Purchase\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends ApiController
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Purchase::where('tenant_id', $request->user()->tenant_id)
            ->with(['supplier', 'company'])
            ->orderByDesc('issue_date');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('supplier_document_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($q) => $q->search($search));
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('issue_date', [$request->input('from'), $request->input('to')]);
        }

        $purchases = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $purchases->items(),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'document_type' => ['required', 'string', 'max:2'],
            'supplier_document_number' => ['required', 'string', 'max:17'],
            'supplier_authorization' => ['nullable', 'string', 'max:49'],
            'issue_date' => ['required', 'date'],
            'authorization_date' => ['nullable', 'date'],
            'payment_methods' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric'],
            'items.*.tax_percentage_code' => ['nullable', 'string'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.main_code' => ['nullable', 'string'],
        ]);

        $tenant = $request->user()->tenant;

        $purchase = $this->purchaseService
            ->forTenant($tenant)
            ->createPurchase(
                collect($validated)->except('items')->toArray(),
                $validated['items']
            );

        return $this->created(['purchase' => $purchase], 'Compra registrada exitosamente');
    }

    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorizePurchase($request, $purchase);

        return $this->success([
            'purchase' => $purchase->load(['supplier', 'items.product', 'company', 'withholdingDocument']),
        ]);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorizePurchase($request, $purchase);

        $validated = $request->validate([
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'document_type' => ['sometimes', 'string', 'max:2'],
            'supplier_document_number' => ['sometimes', 'string', 'max:17'],
            'supplier_authorization' => ['nullable', 'string', 'max:49'],
            'issue_date' => ['sometimes', 'date'],
            'authorization_date' => ['nullable', 'date'],
            'payment_methods' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric'],
            'items.*.tax_percentage_code' => ['nullable', 'string'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.main_code' => ['nullable', 'string'],
        ]);

        $tenant = $request->user()->tenant;

        $purchase = $this->purchaseService
            ->forTenant($tenant)
            ->updatePurchase(
                $purchase,
                collect($validated)->except('items')->toArray(),
                $validated['items']
            );

        return $this->success(['purchase' => $purchase], 'Compra actualizada exitosamente');
    }

    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorizePurchase($request, $purchase);

        $this->purchaseService
            ->forTenant($request->user()->tenant)
            ->voidPurchase($purchase);

        return $this->success(null, 'Compra anulada exitosamente');
    }

    protected function authorizePurchase(Request $request, Purchase $purchase): void
    {
        if ($purchase->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta compra.');
        }
    }
}
