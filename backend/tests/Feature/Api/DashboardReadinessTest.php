<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class DashboardReadinessTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_readiness_returns_checklist_with_signature_days(): void
    {
        Http::fake([
            '*ConsolidadoContribuyente*' => Http::response([[
                'numeroRuc' => $this->company->ruc,
                'razonSocial' => 'X',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'GENERAL',
                'obligadoLlevarContabilidad' => 'NO',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ]]),
        ]);

        $response = $this->getJson('/api/v1/dashboard/readiness');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'ready',
                'checklist' => ['basic_data', 'sri_password', 'digital_signature', 'establishments'],
                'signature_days_remaining',
                'signature_expiring_soon',
                'sri_environment',
                'ruc_active',
            ]])
            ->assertJsonPath('data.ruc_active', true);
    }

    public function test_readiness_tolerates_sri_downtime(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->getJson('/api/v1/dashboard/readiness')
            ->assertOk()
            ->assertJsonPath('data.ruc_active', null);
    }

    public function test_readiness_returns_404_without_company(): void
    {
        $this->company->delete();

        $this->getJson('/api/v1/dashboard/readiness')
            ->assertNotFound();
    }
}
