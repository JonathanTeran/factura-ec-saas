<?php

namespace Tests\Feature;

use App\Notifications\TaxpayerDataChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class RefreshTaxpayerDataTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
    }

    private function fakeSri(array $overrides = []): void
    {
        Http::fake([
            '*ConsolidadoContribuyente*' => Http::response([array_merge([
                'numeroRuc' => $this->company->ruc,
                'razonSocial' => $this->company->business_name,
                'estadoContribuyenteRuc' => 'ACTIVO',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'GENERAL',
                'categoria' => null,
                'obligadoLlevarContabilidad' => 'NO',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ], $overrides)]),
        ]);
    }

    public function test_updates_regime_and_notifies_on_change(): void
    {
        $this->company->update(['rimpe_type' => 'none', 'obligated_accounting' => false]);

        $this->fakeSri([
            'regimen' => 'RIMPE',
            'categoria' => 'EMPRENDEDOR',
            'obligadoLlevarContabilidad' => 'SI',
        ]);

        $this->artisan('sri:refresh-taxpayer-data')->assertSuccessful();

        $this->company->refresh();
        $this->assertEquals('emprendedor', $this->company->rimpe_type);
        $this->assertTrue((bool) $this->company->obligated_accounting);

        Notification::assertSentTo(
            $this->tenant->owner,
            TaxpayerDataChangedNotification::class
        );
    }

    public function test_does_not_notify_when_nothing_changed(): void
    {
        $this->company->update(['rimpe_type' => 'none', 'obligated_accounting' => false]);

        $this->fakeSri();

        $this->artisan('sri:refresh-taxpayer-data')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_keeps_data_when_sri_is_unreachable(): void
    {
        $this->company->update(['rimpe_type' => 'emprendedor']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->artisan('sri:refresh-taxpayer-data')->assertSuccessful();

        $this->company->refresh();
        $this->assertEquals('emprendedor', $this->company->rimpe_type);
        Notification::assertNothingSent();
    }
}
