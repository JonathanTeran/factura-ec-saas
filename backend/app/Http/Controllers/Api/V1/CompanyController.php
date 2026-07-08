<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CompanyRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\EmissionPointResource;
use App\Models\Tenant\Company;
use App\Services\Billing\BillingService;
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
     * Crear empresa
     *
     * Registra un nuevo emisor (RUC) en la cuenta. Valor agregado multi-empresa:
     * el número de empresas permitidas depende del plan. Si se alcanza el tope,
     * responde 403 con `error: limit_reached` para invitar a subir de plan.
     */
    public function store(CompanyRequest $request, BillingService $billing): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $limit = $billing->checkLimit($tenant, 'companies');

        if (! ($limit['allowed'] ?? false)) {
            return response()->json([
                'error' => 'limit_reached',
                'resource' => 'companies',
                'message' => 'Alcanzaste el limite de empresas (RUCs) de tu plan'
                    . ($limit['limit'] >= 0 ? " ({$limit['limit']})" : '')
                    . '. Sube de plan para administrar mas empresas desde una sola cuenta.',
                'limit' => $limit['limit'] ?? 0,
                'used' => $limit['used'] ?? 0,
            ], 403);
        }

        $data = $request->validated();
        $sriPassword = $data['sri_password'] ?? null;
        unset($data['sri_password']);
        $data['tenant_id'] = $tenant->id;

        $company = Company::create($data);

        if (filled($sriPassword)) {
            $company->setSriPassword($sriPassword);
            $company->save();
        }

        return $this->created([
            'company' => new CompanyResource($company->fresh()),
        ], 'Empresa creada correctamente.');
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
     * Actualizar empresa
     *
     * Actualiza los datos del emisor (razón social, régimen, flags
     * tributarios, ambiente SRI y opcionalmente la clave SRI).
     */
    public function update(CompanyRequest $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $data = $request->validated();

        if (filled($data['sri_password'] ?? null)) {
            $company->setSriPassword($data['sri_password']);
        }

        // La clave se maneja aparte (encriptada); logo y settings tienen su propio flujo
        unset($data['sri_password'], $data['logo_path'], $data['settings']);

        $company->fill($data);
        $company->save();

        return $this->success([
            'company' => new CompanyResource($company->fresh()),
        ], 'Empresa actualizada correctamente.');
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

    /**
     * Subir logo
     *
     * Guarda el logo de la empresa (aparece en el RIDE y en el panel).
     */
    public function uploadLogo(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ], [
            'logo.image' => 'El logo debe ser una imagen (PNG, JPG, WebP).',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ]);

        if ($company->logo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($company->logo_path);
        }

        $path = $request->file('logo')->store("logos/{$company->tenant_id}", 'public');
        $company->update(['logo_path' => $path]);

        return $this->success([
            'logo_url' => asset('storage/'.$path),
        ], 'Logo actualizado.');
    }

    /**
     * Eliminar logo
     */
    public function deleteLogo(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        if ($company->logo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($company->logo_path);
            $company->update(['logo_path' => null]);
        }

        return $this->success(['logo_url' => null], 'Logo eliminado.');
    }

    /**
     * Cambia el ambiente SRI de la empresa (1 = Pruebas, 2 = Producción).
     *
     * Endpoint dedicado para poder alternar el ambiente sin reenviar todos los
     * datos fiscales de la empresa. El usuario decide cuándo pasar a Producción.
     */
    public function updateEnvironment(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $data = $request->validate([
            'environment' => ['required', 'in:1,2'],
        ]);

        $company->sri_environment = $data['environment'];
        $company->save();

        return $this->success([
            'company' => new CompanyResource($company->fresh()),
        ], $data['environment'] === '2'
            ? 'Ambiente cambiado a Producción.'
            : 'Ambiente cambiado a Pruebas.');
    }

    /**
     * Eliminar documentos de PRUEBAS
     *
     * Borra definitivamente los comprobantes emitidos en ambiente de pruebas
     * (environment=1) de la empresa: documentos, archivos XML/RIDE del storage
     * y los asientos contables generados por ellos. Los documentos de pruebas
     * no tienen validez fiscal; esto deja limpia la cuenta al pasar a
     * Producción. Requiere confirm=true.
     */
    public function purgeTestDocuments(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $request->validate([
            'confirm' => ['required', 'accepted'],
        ], [
            'confirm.accepted' => 'Debes confirmar la eliminación.',
        ]);

        $docs = \App\Models\SRI\ElectronicDocument::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->where('environment', '1')
            ->get();

        if ($docs->isEmpty()) {
            return $this->success(['deleted' => 0], 'No hay documentos de pruebas para eliminar.');
        }

        $deleted = 0;

        foreach ($docs as $doc) {
            // Archivos en storage (XML firmado/autorizado y RIDE).
            foreach ([$doc->xml_signed_path, $doc->xml_authorized_path, $doc->ride_pdf_path] as $path) {
                if ($path) {
                    try {
                        \Illuminate\Support\Facades\Storage::delete($path);
                    } catch (\Throwable) {
                        // El archivo puede no existir; no bloquea la purga.
                    }
                }
            }

            // Asientos contables generados por este documento (líneas cascadean).
            \App\Models\Accounting\JournalEntry::withoutGlobalScopes()
                ->where('tenant_id', $company->tenant_id)
                ->where('source_document_type', \App\Models\SRI\ElectronicDocument::class)
                ->where('source_document_id', $doc->id)
                ->forceDelete();

            // Items y pagos cascadean por FK; POS/compras/proformas quedan en null.
            $doc->forceDelete();
            $deleted++;
        }

        \App\Services\Cache\TenantCacheService::invalidateTenant($company->tenant_id);

        return $this->success(
            ['deleted' => $deleted],
            "Se eliminaron {$deleted} documentos de pruebas.",
        );
    }

    protected function authorizeCompany(Request $request, Company $company): void
    {
        if ($company->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta empresa.');
        }
    }
}
