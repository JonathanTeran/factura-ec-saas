<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\CostCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostCenterController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = CostCenter::where('company_id', $request->user()->current_company_id ?? 0)
            ->orderBy('code');

        if ($request->boolean('tree')) {
            $centers = $query->whereNull('parent_id')
                ->with('children.children')
                ->get();

            return $this->success(['cost_centers' => $centers]);
        }

        return $this->success(['cost_centers' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:cost_centers,id'],
        ]);

        $level = 1;
        if (!empty($validated['parent_id'])) {
            $parent = CostCenter::findOrFail($validated['parent_id']);
            $level = $parent->level + 1;
        }

        $center = CostCenter::create([
            'tenant_id' => $request->user()->tenant_id,
            'company_id' => $request->user()->current_company_id,
            ...$validated,
            'level' => $level,
        ]);

        return $this->created(['cost_center' => $center], 'Centro de costo creado exitosamente');
    }

    public function show(Request $request, CostCenter $costCenter): JsonResponse
    {
        $this->authorizeCostCenter($request, $costCenter);

        return $this->success([
            'cost_center' => $costCenter->load(['parent', 'children']),
        ]);
    }

    public function update(Request $request, CostCenter $costCenter): JsonResponse
    {
        $this->authorizeCostCenter($request, $costCenter);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $costCenter->update($validated);

        return $this->success(['cost_center' => $costCenter->fresh()], 'Centro de costo actualizado');
    }

    public function destroy(Request $request, CostCenter $costCenter): JsonResponse
    {
        $this->authorizeCostCenter($request, $costCenter);

        if ($costCenter->children()->exists()) {
            return $this->error('No se puede eliminar un centro de costo con sub-centros.');
        }

        $costCenter->delete();

        return $this->success(null, 'Centro de costo eliminado exitosamente');
    }

    protected function authorizeCostCenter(Request $request, CostCenter $costCenter): void
    {
        if ($costCenter->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este centro de costo.');
        }
    }
}
