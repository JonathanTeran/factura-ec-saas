<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class RucLookupApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function fakeSriResponses(): void
    {
        Http::fake([
            '*ConsolidadoContribuyente*' => Http::response([[
                'numeroRuc' => '1207481803001',
                'razonSocial' => 'TERAN TRIANA JONATHAN EDUARDO',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'actividadEconomicaPrincipal' => 'DESARROLLO DE SOFTWARE',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'GENERAL',
                'categoria' => null,
                'obligadoLlevarContabilidad' => 'NO',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ]]),
            '*Establecimiento*' => Http::response([[
                'nombreFantasiaComercial' => null,
                'tipoEstablecimiento' => 'MAT',
                'direccionCompleta' => 'GUAYAS / GUAYAQUIL / XIMENA',
                'estado' => 'ABIERTO',
                'numeroEstablecimiento' => '002',
                'matriz' => 'SI',
            ]]),
        ]);
    }

    public function test_ruc_lookup_returns_taxpayer_and_establishments(): void
    {
        $this->fakeSriResponses();

        $response = $this->getJson('/api/v1/sri/ruc/1207481803001');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_name', 'TERAN TRIANA JONATHAN EDUARDO')
            ->assertJsonPath('data.status', 'ACTIVO')
            ->assertJsonPath('data.taxpayer_type', 'natural')
            ->assertJsonPath('data.regime', 'general')
            ->assertJsonPath('data.obligated_accounting', false)
            ->assertJsonPath('data.establishments.0.code', '002')
            ->assertJsonPath('data.establishments.0.is_main', true);
    }

    public function test_ruc_lookup_returns_404_when_not_found(): void
    {
        Http::fake(['srienlinea.sri.gob.ec/*' => Http::response([])]);

        $this->getJson('/api/v1/sri/ruc/9999999999001')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_ruc_lookup_rejects_invalid_ruc_format(): void
    {
        $this->getJson('/api/v1/sri/ruc/123')
            ->assertStatus(422);
    }

    public function test_identification_lookup_accepts_cedula(): void
    {
        $this->fakeSriResponses();

        $this->getJson('/api/v1/sri/identification/1207481803')
            ->assertOk()
            ->assertJsonPath('data.business_name', 'TERAN TRIANA JONATHAN EDUARDO')
            ->assertJsonPath('data.status', 'ACTIVO');
    }

    public function test_identification_lookup_returns_404_when_not_registered(): void
    {
        Http::fake(['srienlinea.sri.gob.ec/*' => Http::response([])]);

        $this->getJson('/api/v1/sri/identification/0912345678')
            ->assertNotFound();
    }

    public function test_identification_lookup_rejects_invalid_format(): void
    {
        $this->getJson('/api/v1/sri/identification/12ab')
            ->assertStatus(422);
    }

    public function test_import_establishments_creates_missing_open_branches(): void
    {
        Http::fake([
            '*Establecimiento*' => Http::response([
                [
                    'nombreFantasiaComercial' => 'SUCURSAL NORTE',
                    'direccionCompleta' => 'PICHINCHA / QUITO / NORTE',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => '007',
                    'matriz' => 'NO',
                ],
                [
                    'nombreFantasiaComercial' => 'YA EXISTE',
                    'direccionCompleta' => 'GUAYAS / GUAYAQUIL',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => $this->branch->code,
                    'matriz' => 'SI',
                ],
            ]),
        ]);

        $response = $this->postJson('/api/v1/sri/import-establishments');

        $response->assertOk()
            ->assertJsonCount(1, 'data.imported')
            ->assertJsonPath('data.imported.0.code', '007');

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '007',
            'name' => 'SUCURSAL NORTE',
        ]);

        $imported = \App\Models\Tenant\Branch::where('code', '007')->first();
        $this->assertCount(1, $imported->emissionPoints);
    }

    public function test_import_establishments_fails_gracefully_when_sri_down(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->postJson('/api/v1/sri/import-establishments')
            ->assertStatus(503);
    }
}
