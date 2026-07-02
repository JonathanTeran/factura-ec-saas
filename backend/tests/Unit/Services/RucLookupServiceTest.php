<?php

namespace Tests\Unit\Services;

use App\Services\SRI\RucLookupService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RucLookupServiceTest extends TestCase
{
    private function fakeContribuyente(array $overrides = []): array
    {
        return array_merge([
            'numeroRuc' => '1207481803001',
            'razonSocial' => 'TERAN TRIANA JONATHAN EDUARDO',
            'estadoContribuyenteRuc' => 'ACTIVO',
            'actividadEconomicaPrincipal' => 'ACTIVIDADES DE DISEÑO DE SOFTWARE',
            'tipoContribuyente' => 'PERSONA NATURAL',
            'regimen' => 'GENERAL',
            'categoria' => null,
            'obligadoLlevarContabilidad' => 'NO',
            'agenteRetencion' => 'NO',
            'contribuyenteEspecial' => 'NO',
        ], $overrides);
    }

    public function test_lookup_returns_normalized_taxpayer_data(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente()]),
        ]);

        $result = app(RucLookupService::class)->lookup('1207481803001');

        $this->assertNotNull($result);
        $this->assertEquals('1207481803001', $result['ruc']);
        $this->assertEquals('TERAN TRIANA JONATHAN EDUARDO', $result['business_name']);
        $this->assertEquals('ACTIVO', $result['status']);
        $this->assertEquals('natural', $result['taxpayer_type']);
        $this->assertEquals('general', $result['regime']);
        $this->assertFalse($result['obligated_accounting']);
        $this->assertFalse($result['retention_agent']);
        $this->assertFalse($result['special_taxpayer']);
        $this->assertEquals('ACTIVIDADES DE DISEÑO DE SOFTWARE', $result['main_activity']);
    }

    public function test_lookup_maps_juridical_rimpe_and_boolean_flags(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente([
                'tipoContribuyente' => 'SOCIEDAD',
                'regimen' => 'RIMPE',
                'categoria' => 'EMPRENDEDOR',
                'obligadoLlevarContabilidad' => 'SI',
                'agenteRetencion' => 'SI',
                'contribuyenteEspecial' => 'SI',
            ])]),
        ]);

        $result = app(RucLookupService::class)->lookup('1790012345001');

        $this->assertEquals('juridical', $result['taxpayer_type']);
        $this->assertEquals('rimpe_emprendedor', $result['regime']);
        $this->assertTrue($result['obligated_accounting']);
        $this->assertTrue($result['retention_agent']);
        $this->assertTrue($result['special_taxpayer']);
    }

    public function test_lookup_maps_rimpe_negocio_popular(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente([
                'regimen' => 'RIMPE',
                'categoria' => 'NEGOCIO POPULAR',
            ])]),
        ]);

        $result = app(RucLookupService::class)->lookup('1207481803001');

        $this->assertEquals('rimpe_popular', $result['regime']);
    }

    public function test_lookup_returns_null_when_ruc_not_found(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([]),
        ]);

        $this->assertNull(app(RucLookupService::class)->lookup('9999999999001'));
    }

    public function test_lookup_returns_null_on_invalid_ruc_without_calling_sri(): void
    {
        Http::fake();

        $this->assertNull(app(RucLookupService::class)->lookup('123'));
        $this->assertNull(app(RucLookupService::class)->lookup('abcdefghijklm'));

        Http::assertNothingSent();
    }

    public function test_lookup_returns_null_when_sri_is_unreachable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->assertNull(app(RucLookupService::class)->lookup('1207481803001'));
    }

    public function test_lookup_sends_browser_user_agent(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente()]),
        ]);

        app(RucLookupService::class)->lookup('1207481803001');

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent')
                && str_contains($request->header('User-Agent')[0], 'Mozilla');
        });
    }

    public function test_lookup_identification_with_cedula_queries_catastro_as_ruc(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente()]),
        ]);

        $result = app(RucLookupService::class)->lookupIdentification('1207481803');

        $this->assertNotNull($result);
        $this->assertEquals('TERAN TRIANA JONATHAN EDUARDO', $result['business_name']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ruc=1207481803001');
        });
    }

    public function test_lookup_identification_with_ruc_behaves_like_lookup(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([$this->fakeContribuyente()]),
        ]);

        $result = app(RucLookupService::class)->lookupIdentification('1207481803001');

        $this->assertEquals('1207481803001', $result['ruc']);
    }

    public function test_lookup_identification_returns_null_for_invalid_input(): void
    {
        Http::fake();

        $service = app(RucLookupService::class);

        $this->assertNull($service->lookupIdentification('12345'));
        $this->assertNull($service->lookupIdentification('abcdefghij'));

        Http::assertNothingSent();
    }

    public function test_establishments_returns_normalized_list(): void
    {
        Http::fake([
            'srienlinea.sri.gob.ec/*' => Http::response([
                [
                    'nombreFantasiaComercial' => 'GOLDFISH',
                    'tipoEstablecimiento' => 'OFI',
                    'direccionCompleta' => 'LOS RIOS / VINCES / VINCES /  S/N',
                    'estado' => 'CERRADO',
                    'numeroEstablecimiento' => '001',
                    'matriz' => 'NO',
                ],
                [
                    'nombreFantasiaComercial' => null,
                    'tipoEstablecimiento' => 'MAT',
                    'direccionCompleta' => 'GUAYAS / GUAYAQUIL / XIMENA / 50C SE 14',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => '002',
                    'matriz' => 'SI',
                ],
            ]),
        ]);

        $result = app(RucLookupService::class)->establishments('1207481803001');

        $this->assertCount(2, $result);
        $this->assertEquals('001', $result[0]['code']);
        $this->assertEquals('GOLDFISH', $result[0]['trade_name']);
        $this->assertFalse($result[0]['is_open']);
        $this->assertFalse($result[0]['is_main']);
        $this->assertEquals('002', $result[1]['code']);
        $this->assertNull($result[1]['trade_name']);
        $this->assertEquals('GUAYAS / GUAYAQUIL / XIMENA / 50C SE 14', $result[1]['address']);
        $this->assertTrue($result[1]['is_open']);
        $this->assertTrue($result[1]['is_main']);
    }

    public function test_establishments_returns_empty_array_on_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->assertSame([], app(RucLookupService::class)->establishments('1207481803001'));
    }
}
