<?php

namespace Tests\Feature;

use App\Livewire\Panel\Settings\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CompanySettingsRucLookupTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_lookup_ruc_refreshes_tax_data_from_sri(): void
    {
        Http::fake([
            '*ConsolidadoContribuyente*' => Http::response([[
                'numeroRuc' => $this->company->ruc,
                'razonSocial' => 'RAZON SOCIAL ACTUALIZADA',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'actividadEconomicaPrincipal' => 'COMERCIO',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'RIMPE',
                'categoria' => 'NEGOCIO POPULAR',
                'obligadoLlevarContabilidad' => 'SI',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'SI',
            ]]),
            '*Establecimiento*' => Http::response([]),
        ]);

        $this->actingAs($this->user);

        Livewire::test(CompanySettings::class)
            ->call('lookupRuc')
            ->assertHasNoErrors()
            ->assertSet('business_name', 'RAZON SOCIAL ACTUALIZADA')
            ->assertSet('taxpayer_type', 'natural')
            ->assertSet('tax_regime', 'rimpe_popular')
            ->assertSet('accounting_required', true)
            ->assertSet('special_taxpayer', true)
            ->assertSet('rucLookupStatus', 'ACTIVO');
    }

    public function test_import_sri_establishments_creates_missing_branches(): void
    {
        Http::fake([
            '*Establecimiento*' => Http::response([
                [
                    'nombreFantasiaComercial' => 'SUCURSAL NORTE',
                    'direccionCompleta' => 'PICHINCHA / QUITO / NORTE',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => '005',
                    'matriz' => 'NO',
                ],
                [
                    'nombreFantasiaComercial' => 'CERRADA',
                    'direccionCompleta' => 'LOS RIOS / VINCES',
                    'estado' => 'CERRADO',
                    'numeroEstablecimiento' => '004',
                    'matriz' => 'NO',
                ],
            ]),
        ]);

        $this->actingAs($this->user);

        Livewire::test(CompanySettings::class)
            ->call('importSriEstablishments')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '005',
            'name' => 'SUCURSAL NORTE',
            'is_main' => false,
        ]);
        $this->assertDatabaseMissing('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '004',
        ]);

        // El establecimiento importado queda operativo: punto de emisión + secuenciales
        $imported = \App\Models\Tenant\Branch::where('code', '005')->first();
        $this->assertCount(1, $imported->emissionPoints);
        $this->assertDatabaseHas('sequential_numbers', [
            'emission_point_id' => $imported->emissionPoints->first()->id,
            'document_type' => '01',
        ]);
    }

    public function test_import_sri_establishments_skips_existing_codes(): void
    {
        Http::fake([
            '*Establecimiento*' => Http::response([
                [
                    'nombreFantasiaComercial' => 'DUPLICADA',
                    'direccionCompleta' => 'GUAYAS / GUAYAQUIL',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => $this->branch->code,
                    'matriz' => 'SI',
                ],
            ]),
        ]);

        $this->actingAs($this->user);

        $before = \App\Models\Tenant\Branch::count();

        Livewire::test(CompanySettings::class)
            ->call('importSriEstablishments')
            ->assertHasNoErrors();

        $this->assertEquals($before, \App\Models\Tenant\Branch::count());
    }

    public function test_lookup_ruc_adds_error_when_sri_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->actingAs($this->user);

        Livewire::test(CompanySettings::class)
            ->call('lookupRuc')
            ->assertHasErrors(['ruc']);
    }
}
