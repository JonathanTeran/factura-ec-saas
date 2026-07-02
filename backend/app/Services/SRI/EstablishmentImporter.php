<?php

namespace App\Services\SRI;

use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;

/**
 * Importa las sucursales abiertas del catastro del SRI que aún no existen
 * para la empresa, cada una con su punto de emisión 001 y secuenciales
 * inicializados, listas para emitir.
 */
class EstablishmentImporter
{
    public function __construct(
        private readonly RucLookupService $lookupService,
    ) {}

    /**
     * @return array<int, array{id: int, code: string, name: string, address: string}>|null
     *         Sucursales creadas; null si el SRI no respondió (todo RUC tiene
     *         al menos su matriz, así que una lista vacía indica fallo).
     */
    public function import(Company $company): ?array
    {
        $establishments = $this->lookupService->establishments($company->ruc);

        if ($establishments === []) {
            return null;
        }

        $existingCodes = $company->branches()->pluck('code')->all();
        $imported = [];

        foreach ($establishments as $establishment) {
            if (! $establishment['is_open'] || in_array($establishment['code'], $existingCodes, true)) {
                continue;
            }

            $branch = Branch::create([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'code' => $establishment['code'],
                'name' => $establishment['trade_name'] ?: 'Establecimiento ' . $establishment['code'],
                'address' => $establishment['address'] ?? '',
                'is_main' => false,
                'is_active' => true,
            ]);

            $emissionPoint = EmissionPoint::create([
                'tenant_id' => $company->tenant_id,
                'branch_id' => $branch->id,
                'code' => '001',
                'name' => 'Punto de Emisión 1',
                'is_active' => true,
            ]);

            foreach (array_keys(config('sri.document_types')) as $docType) {
                SequentialNumber::firstOrCreate([
                    'tenant_id' => $company->tenant_id,
                    'emission_point_id' => $emissionPoint->id,
                    'document_type' => $docType,
                ], [
                    'current_number' => 0,
                ]);
            }

            $imported[] = [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'address' => $branch->address,
            ];
        }

        return $imported;
    }
}
