<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\Company;
use App\Services\SRI\EstablishmentImporter;
use App\Services\SRI\RucLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SriLookupController extends ApiController
{
    public function __construct(
        private readonly RucLookupService $lookupService,
    ) {}

    /**
     * Consulta pública del catastro del SRI: datos del contribuyente
     * y sus establecimientos, para autocompletar la configuración del emisor.
     */
    public function ruc(string $ruc): JsonResponse
    {
        if (preg_match('/^[0-9]{13}$/', $ruc) !== 1) {
            return $this->validationError(
                ['ruc' => ['El RUC debe tener 13 dígitos numéricos.']],
            );
        }

        $taxpayer = $this->lookupService->lookup($ruc);

        if ($taxpayer === null) {
            return $this->notFound('RUC no encontrado en el catastro del SRI.');
        }

        $taxpayer['establishments'] = $this->lookupService->establishments($ruc);

        return $this->success($taxpayer);
    }

    /**
     * Consulta por cédula (10 dígitos) o RUC (13) — para autocompletar
     * clientes y proveedores.
     */
    public function identification(string $identification): JsonResponse
    {
        if (preg_match('/^([0-9]{10}|[0-9]{13})$/', $identification) !== 1) {
            return $this->validationError(
                ['identification' => ['La identificación debe tener 10 dígitos (cédula) o 13 (RUC).']],
            );
        }

        $taxpayer = $this->lookupService->lookupIdentification($identification);

        if ($taxpayer === null) {
            return $this->notFound('Identificación no encontrada en el catastro del SRI.');
        }

        // La cédula se consulta con el RUC de persona natural (cédula + "001");
        // reutilizamos el mismo cómputo para traer la dirección de la matriz.
        $ruc = strlen($identification) === 10 ? $identification.'001' : $identification;
        $establishments = $this->lookupService->establishments($ruc);
        $main = collect($establishments)->firstWhere('is_main', true) ?? ($establishments[0] ?? null);
        $taxpayer['address'] = $main['address'] ?? null;

        return $this->success($taxpayer);
    }

    /**
     * Establecimientos del RUC de la empresa según el catastro del SRI,
     * indicando cuáles ya están configurados en Facturón (por código).
     *
     * @queryParam company_id int Empresa del tenant (por defecto la primera).
     */
    public function establishments(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->notFound('Aún no has configurado tu empresa.');
        }

        $fromSri = $this->lookupService->establishments($company->ruc);

        if ($fromSri === []) {
            return $this->error('No se pudieron obtener los establecimientos desde el SRI. Intenta nuevamente más tarde.', 503);
        }

        $local = $company->branches()->get()->keyBy(fn ($branch) => str_pad((string) $branch->code, 3, '0', STR_PAD_LEFT));

        $establishments = collect($fromSri)->map(function (array $item) use ($local) {
            $branch = $local->get(str_pad((string) $item['code'], 3, '0', STR_PAD_LEFT));

            return $item + [
                'configured' => $branch !== null,
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name,
                'branch_is_active' => $branch ? (bool) $branch->is_active : null,
            ];
        })->values();

        return $this->success([
            'company' => [
                'id' => $company->id,
                'ruc' => $company->ruc,
                'business_name' => $company->business_name,
            ],
            'establishments' => $establishments,
            'pending_import' => $establishments->filter(fn (array $e) => $e['is_open'] && ! $e['configured'])->count(),
        ]);
    }

    /**
     * Importa las sucursales abiertas del SRI que aún no existen para la
     * empresa (cada una con su punto de emisión 001 listo para numerar).
     *
     * @bodyParam company_id int Empresa del tenant (por defecto la primera).
     */
    public function importEstablishments(Request $request, EstablishmentImporter $importer): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->notFound('Aún no has configurado tu empresa.');
        }

        $imported = $importer->import($company);

        if ($imported === null) {
            return $this->error('No se pudieron obtener los establecimientos desde el SRI. Intenta nuevamente más tarde.', 503);
        }

        return $this->success(
            ['imported' => $imported],
            $imported === []
                ? 'No hay establecimientos nuevos para importar.'
                : 'Se importaron '.count($imported).' establecimiento(s) desde el SRI.'
        );
    }

    /** Empresa indicada (solo del tenant) o, si no se indica, la primera. */
    private function resolveCompany(Request $request): ?Company
    {
        $request->validate(['company_id' => ['nullable', 'integer']]);

        $companies = $request->user()->tenant->companies();

        if ($request->filled('company_id')) {
            return $companies->whereKey($request->integer('company_id'))->first();
        }

        return $companies->first();
    }
}
