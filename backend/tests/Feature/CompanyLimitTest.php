<?php

namespace Tests\Feature;

use App\Models\Tenant\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CompanyLimitTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'ruc' => '0993212345001',
            'business_name' => 'Empresa Dos S.A.',
            'taxpayer_type' => 'juridical',
            'address' => 'Av. Siempre Viva 123',
            'sri_environment' => '1',
            'email' => 'dos@ejemplo.com',
        ], $override);
    }

    public function test_can_create_company_when_under_plan_limit(): void
    {
        // Plan permite 3 empresas y solo existe 1 (la del trait).
        $this->plan->update(['max_companies' => 3]);

        $this->postJson('/api/v1/companies', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.company.ruc', '0993212345001');

        $this->assertDatabaseHas('companies', [
            'tenant_id' => $this->tenant->id,
            'ruc' => '0993212345001',
        ]);
    }

    public function test_cannot_create_company_when_plan_limit_reached(): void
    {
        // Plan de 1 empresa y ya existe 1 => se alcanzó el tope.
        $this->plan->update(['max_companies' => 1]);

        $this->postJson('/api/v1/companies', $this->payload())
            ->assertStatus(403)
            ->assertJson([
                'error' => 'limit_reached',
                'resource' => 'companies',
                'limit' => 1,
                'used' => 1,
            ]);

        $this->assertDatabaseMissing('companies', [
            'ruc' => '0993212345001',
        ]);
    }

    public function test_unlimited_plan_allows_creating_companies(): void
    {
        $this->plan->update(['max_companies' => -1]);

        // Crear varias por encima de cualquier tope fijo.
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/v1/companies', $this->payload([
                'ruc' => '099321234500' . $i,
                'email' => "empresa{$i}@ejemplo.com",
            ]))->assertStatus(201);
        }

        $this->assertSame(4, Company::where('tenant_id', $this->tenant->id)->count());
    }
}
