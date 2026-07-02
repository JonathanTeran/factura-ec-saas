<?php

namespace Tests\Feature\Api;

use App\Models\Tenant\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CompanyUpdateApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'ruc' => $this->company->ruc,
            'business_name' => 'RAZON SOCIAL ACTUALIZADA',
            'trade_name' => 'NOMBRE COMERCIAL NUEVO',
            'taxpayer_type' => 'natural',
            'rimpe_type' => 'emprendedor',
            'address' => 'DIRECCION ACTUALIZADA',
            'special_taxpayer' => true,
            'special_taxpayer_number' => 'RES-123',
            'retention_agent_number' => 'AGT-456',
            'obligated_accounting' => true,
            'sri_environment' => '2',
            'email' => 'emisor@empresa.com',
            'phone' => '0999999999',
        ], $overrides);
    }

    public function test_updates_company_emitter_data(): void
    {
        $response = $this->putJson("/api/v1/companies/{$this->company->id}", $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company.business_name', 'RAZON SOCIAL ACTUALIZADA')
            ->assertJsonPath('data.company.rimpe_type', 'emprendedor')
            ->assertJsonPath('data.company.is_special_taxpayer', true)
            ->assertJsonPath('data.company.special_taxpayer_number', 'RES-123')
            ->assertJsonPath('data.company.is_accounting_required', true)
            ->assertJsonPath('data.company.sri_environment', '2');

        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'business_name' => 'RAZON SOCIAL ACTUALIZADA',
            'rimpe_type' => 'emprendedor',
            'sri_environment' => '2',
        ]);
    }

    public function test_updates_sri_password_when_provided(): void
    {
        $this->putJson(
            "/api/v1/companies/{$this->company->id}",
            $this->validPayload(['sri_password' => 'nueva-clave-sri'])
        )->assertOk();

        $company = Company::find($this->company->id);
        $this->assertTrue($company->hasSriPassword());
        $this->assertEquals('nueva-clave-sri', $company->getDecryptedSriPassword());
    }

    public function test_keeps_existing_sri_password_when_omitted(): void
    {
        $this->company->setSriPassword('clave-original');
        $this->company->save();

        $this->putJson("/api/v1/companies/{$this->company->id}", $this->validPayload())
            ->assertOk();

        $this->assertEquals(
            'clave-original',
            Company::find($this->company->id)->getDecryptedSriPassword()
        );
    }

    public function test_rejects_company_from_another_tenant(): void
    {
        $other = $this->createSecondTenant();
        $otherCompany = Company::factory()->create(['tenant_id' => $other['tenant']->id]);

        $this->putJson("/api/v1/companies/{$otherCompany->id}", $this->validPayload(['ruc' => $otherCompany->ruc]))
            ->assertStatus(404);
    }

    public function test_rejects_invalid_ruc(): void
    {
        $this->putJson(
            "/api/v1/companies/{$this->company->id}",
            $this->validPayload(['ruc' => '123'])
        )->assertStatus(422);
    }
}
