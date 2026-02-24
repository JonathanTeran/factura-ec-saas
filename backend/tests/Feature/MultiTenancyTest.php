<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_tenant_a_cannot_see_tenant_b_customers(): void
    {
        // Create customer for tenant A
        $customerA = $this->createCustomer(['name' => 'Cliente Tenant A']);

        // Create tenant B with its own customer
        $second = $this->createSecondTenant();
        $customerB = Customer::factory()->create([
            'tenant_id' => $second['tenant']->id,
            'name' => 'Cliente Tenant B',
        ]);

        // As tenant A, list customers
        $response = $this->getJson('/api/v1/customers');

        $response->assertOk();
        $data = $response->json('data');

        // Should only see tenant A's customers
        $names = collect($data)->pluck('name')->toArray();
        $this->assertContains('Cliente Tenant A', $names);
        $this->assertNotContains('Cliente Tenant B', $names);
    }

    public function test_tenant_a_cannot_see_tenant_b_documents(): void
    {
        $docA = $this->createDocument();

        // Switch to tenant B
        $second = $this->createSecondTenant();
        Sanctum::actingAs($second['user']);
        config(['app.tenant_id' => $second['tenant']->id]);

        $response = $this->getJson("/api/v1/documents/{$docA->id}");

        // Global scope makes the document invisible (404), not forbidden (403)
        $response->assertNotFound();
    }

    public function test_creating_customer_auto_assigns_tenant_id(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'identification_type' => '05',
            'identification_number' => '0999999999',
            'name' => 'Auto Tenant Customer',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Auto Tenant Customer',
        ]);
    }

    public function test_inactive_tenant_cannot_access_panel(): void
    {
        $inactiveTenant = Tenant::factory()->create(['status' => 'suspended']);
        $inactiveUser = User::factory()->create([
            'tenant_id' => $inactiveTenant->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($inactiveUser);
        config(['app.tenant_id' => $inactiveTenant->id]);

        $response = $this->getJson('/api/v1/customers');

        // Should be blocked by tenant middleware
        $this->assertTrue(
            in_array($response->status(), [401, 403, 423]),
            "Expected 401, 403, or 423 but got {$response->status()}"
        );
    }
}
