<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('business_name');

        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $suppliers = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identification_type' => ['required', 'string', 'max:2'],
            'identification' => ['required', 'string', 'max:20'],
            'business_name' => ['required', 'string', 'max:255'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_withholding_agent' => ['boolean'],
            'accounting_account' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$validated,
        ]);

        return $this->created(['supplier' => $supplier], 'Proveedor creado exitosamente');
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeSupplier($request, $supplier);

        return $this->success([
            'supplier' => $supplier->loadCount('purchases'),
        ]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeSupplier($request, $supplier);

        $validated = $request->validate([
            'identification_type' => ['sometimes', 'string', 'max:2'],
            'identification' => ['sometimes', 'string', 'max:20'],
            'business_name' => ['sometimes', 'string', 'max:255'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_withholding_agent' => ['boolean'],
            'accounting_account' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return $this->success(['supplier' => $supplier], 'Proveedor actualizado exitosamente');
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeSupplier($request, $supplier);

        if ($supplier->purchases()->exists()) {
            return $this->error('No se puede eliminar el proveedor porque tiene compras registradas.', 400);
        }

        $supplier->delete();

        return $this->success(null, 'Proveedor eliminado exitosamente');
    }

    public function search(Request $request, string $query): JsonResponse
    {
        $suppliers = Supplier::where('tenant_id', $request->user()->tenant_id)
            ->active()
            ->search($query)
            ->limit(10)
            ->get();

        return $this->success(['suppliers' => $suppliers]);
    }

    protected function authorizeSupplier(Request $request, Supplier $supplier): void
    {
        if ($supplier->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este proveedor.');
        }
    }
}
