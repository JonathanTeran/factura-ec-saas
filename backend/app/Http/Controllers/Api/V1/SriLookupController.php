<?php

namespace App\Http\Controllers\Api\V1;

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

        return $this->success($taxpayer);
    }

    /**
     * Importa las sucursales abiertas del SRI que aún no existen para la
     * empresa del tenant.
     */
    public function importEstablishments(Request $request, EstablishmentImporter $importer): JsonResponse
    {
        $company = $request->user()->tenant->companies()->first();

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
                : 'Se importaron ' . count($imported) . ' establecimiento(s) desde el SRI.'
        );
    }
}
