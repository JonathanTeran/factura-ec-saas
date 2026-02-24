<?php

namespace Tests\Feature\Api;

use App\Models\Tenant\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_can_list_customers(): void
    {
        $this->createCustomer();
        $this->createCustomer();
        $this->createCustomer();

        $response = $this->getJson('/api/v1/customers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'identification_type' => '05',
            'identification_number' => '0912345678',
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '0991234567',
            'address' => 'Guayaquil, Ecuador',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'identification' => '0912345678',
            'name' => 'Juan Pérez',
        ]);
    }

    public function test_can_update_customer(): void
    {
        $customer = $this->createCustomer();

        $response = $this->putJson("/api/v1/customers/{$customer->id}", [
            'identification_type' => $customer->identification_type->value,
            'identification_number' => $customer->identification,
            'name' => 'Nombre Actualizado',
            'email' => 'updated@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $customer->refresh();
        $this->assertEquals('Nombre Actualizado', $customer->name);
    }

    public function test_customer_scoped_to_tenant(): void
    {
        $customer = $this->createCustomer();

        // Switch to second tenant
        $second = $this->createSecondTenant();
        Sanctum::actingAs($second['user']);
        config(['app.tenant_id' => $second['tenant']->id]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}");

        // Global scope makes the customer invisible (404), not forbidden (403)
        $response->assertNotFound();
    }
}
