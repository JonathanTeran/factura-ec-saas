<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\EmissionPointResource;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $branches = $company->branches()
            ->with('emissionPoints')
            ->orderBy('code')
            ->get();

        return $this->success([
            'branches' => BranchResource::collection($branches),
        ]);
    }

    public function store(BranchRequest $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $branch = $company->branches()->create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        // Si es la primera o se marca como principal, actualizar las demás
        if ($branch->is_main) {
            $company->branches()
                ->where('id', '!=', $branch->id)
                ->update(['is_main' => false]);
        }

        return $this->created([
            'branch' => new BranchResource($branch),
        ], 'Establecimiento creado exitosamente');
    }

    public function show(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorizeCompany($request, $company);
        $this->authorizeBranch($company, $branch);

        $branch->load('emissionPoints');

        return $this->success([
            'branch' => new BranchResource($branch),
        ]);
    }

    public function update(BranchRequest $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorizeCompany($request, $company);
        $this->authorizeBranch($company, $branch);

        $branch->update($request->validated());

        if ($branch->is_main) {
            $company->branches()
                ->where('id', '!=', $branch->id)
                ->update(['is_main' => false]);
        }

        return $this->success([
            'branch' => new BranchResource($branch),
        ], 'Establecimiento actualizado exitosamente');
    }

    public function destroy(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorizeCompany($request, $company);
        $this->authorizeBranch($company, $branch);

        if ($branch->documents()->exists()) {
            return $this->error(
                'No se puede eliminar el establecimiento porque tiene documentos emitidos.',
                400
            );
        }

        $branch->emissionPoints()->delete();
        $branch->delete();

        return $this->success(null, 'Establecimiento eliminado exitosamente');
    }

    public function emissionPoints(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorizeCompany($request, $company);
        $this->authorizeBranch($company, $branch);

        $emissionPoints = $branch->emissionPoints()
            ->orderBy('code')
            ->get();

        return $this->success([
            'emission_points' => EmissionPointResource::collection($emissionPoints),
        ]);
    }

    protected function authorizeCompany(Request $request, Company $company): void
    {
        if ($company->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta empresa.');
        }
    }

    protected function authorizeBranch(Company $company, Branch $branch): void
    {
        if ($branch->company_id !== $company->id) {
            abort(404, 'Establecimiento no encontrado.');
        }
    }
}
