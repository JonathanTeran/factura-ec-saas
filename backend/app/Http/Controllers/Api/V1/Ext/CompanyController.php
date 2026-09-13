<?php

namespace App\Http\Controllers\Api\V1\Ext;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/ext/companies — empresas emisoras del tenant con sus
 * establecimientos y puntos de emisión activos: los ids que el integrador
 * necesita para POST /documents (company_id, emission_point_id).
 */
class CompanyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::where('tenant_id', $request->user()->tenant_id)
            ->with(['branches' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('code')
                    ->with(['emissionPoints' => fn ($q) => $q->where('is_active', true)->orderBy('code')]);
            }])
            ->orderBy('id')
            ->get();

        return $this->success([
            'companies' => $companies->map(fn (Company $company) => [
                'id' => $company->id,
                'ruc' => $company->ruc,
                'business_name' => $company->business_name,
                'trade_name' => $company->trade_name,
                'sri_environment' => (string) $company->sri_environment,
                'sri_environment_label' => (string) $company->sri_environment === '2' ? 'Producción' : 'Pruebas',
                'is_active' => (bool) $company->is_active,
                'has_signature' => method_exists($company, 'hasValidSignature') ? (bool) $company->hasValidSignature() : null,
                'branches' => $company->branches->map(fn ($branch) => [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'is_main' => (bool) $branch->is_main,
                    'emission_points' => $branch->emissionPoints->map(fn ($point) => [
                        'id' => $point->id,
                        'code' => $point->code,
                        'name' => $point->name,
                        'series' => $branch->code.'-'.$point->code,
                    ])->values(),
                ])->values(),
            ])->values(),
        ]);
    }
}
