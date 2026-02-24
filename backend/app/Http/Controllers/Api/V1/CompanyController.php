<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BranchResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\EmissionPointResource;
use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Empresas
 */
class CompanyController extends ApiController
{
    /**
     * Listar empresas
     *
     * Retorna todas las empresas del tenant con sus establecimientos.
     */
    public function index(Request $request): JsonResponse
    {
        $companies = Company::where('tenant_id', $request->user()->tenant_id)
            ->with(['branches'])
            ->orderBy('business_name')
            ->get();

        return $this->success([
            'companies' => CompanyResource::collection($companies),
        ]);
    }

    /**
     * Ver empresa
     *
     * Retorna los datos de una empresa con establecimientos y puntos de emisión.
     */
    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $company->load(['branches.emissionPoints']);

        return $this->success([
            'company' => new CompanyResource($company),
        ]);
    }

    /**
     * Cambiar empresa activa
     *
     * Establece esta empresa como la empresa activa del usuario.
     */
    public function switch(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        // Update user's current company
        $request->user()->update([
            'current_company_id' => $company->id,
        ]);

        return $this->success([
            'company' => new CompanyResource($company->load('branches.emissionPoints')),
        ], 'Empresa cambiada exitosamente');
    }

    /**
     * Establecimientos de la empresa
     *
     * Retorna los establecimientos (sucursales) con sus puntos de emisión.
     */
    public function branches(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $branches = $company->branches()
            ->with('emissionPoints')
            ->orderBy('name')
            ->get();

        return $this->success([
            'branches' => BranchResource::collection($branches),
        ]);
    }

    /**
     * Puntos de emisión de la empresa
     *
     * Retorna todos los puntos de emisión de la empresa.
     */
    public function emissionPoints(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $emissionPoints = $company->branches()
            ->with('emissionPoints')
            ->get()
            ->pluck('emissionPoints')
            ->flatten();

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
}
