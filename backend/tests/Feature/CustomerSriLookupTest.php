<?php

namespace Tests\Feature;

use App\Livewire\Panel\Customers\CustomerForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CustomerSriLookupTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function fakeSri(): void
    {
        Http::fake([
            '*ConsolidadoContribuyente*' => Http::response([[
                'numeroRuc' => '1207481803001',
                'razonSocial' => 'TERAN TRIANA JONATHAN EDUARDO',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'GENERAL',
                'obligadoLlevarContabilidad' => 'NO',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ]]),
            '*Establecimiento*' => Http::response([[
                'nombreFantasiaComercial' => null,
                'direccionCompleta' => 'GUAYAS / GUAYAQUIL / XIMENA',
                'estado' => 'ABIERTO',
                'numeroEstablecimiento' => '002',
                'matriz' => 'SI',
            ]]),
        ]);
    }

    public function test_lookup_sri_fills_business_name_from_cedula(): void
    {
        $this->fakeSri();
        $this->actingAs($this->user);

        Livewire::test(CustomerForm::class)
            ->set('identification_type', 'cedula')
            ->set('identification', '1207481803')
            ->call('lookupSri')
            ->assertHasNoErrors()
            ->assertSet('business_name', 'TERAN TRIANA JONATHAN EDUARDO')
            ->assertSet('address', 'GUAYAS / GUAYAQUIL / XIMENA');
    }

    public function test_lookup_sri_does_not_overwrite_existing_business_name(): void
    {
        $this->fakeSri();
        $this->actingAs($this->user);

        Livewire::test(CustomerForm::class)
            ->set('identification_type', 'cedula')
            ->set('identification', '1207481803')
            ->set('business_name', 'NOMBRE MANUAL')
            ->call('lookupSri')
            ->assertSet('business_name', 'NOMBRE MANUAL');
    }

    public function test_lookup_sri_adds_error_when_not_found(): void
    {
        Http::fake(['srienlinea.sri.gob.ec/*' => Http::response([])]);
        $this->actingAs($this->user);

        Livewire::test(CustomerForm::class)
            ->set('identification_type', 'cedula')
            ->set('identification', '0912345678')
            ->call('lookupSri')
            ->assertHasErrors(['identification']);
    }

    public function test_lookup_sri_ignores_invalid_identification(): void
    {
        Http::fake();
        $this->actingAs($this->user);

        Livewire::test(CustomerForm::class)
            ->set('identification', '123')
            ->call('lookupSri')
            ->assertHasErrors(['identification']);

        Http::assertNothingSent();
    }
}
