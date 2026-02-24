<?php

namespace Tests\Feature\Api;

use App\Models\Tenant\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_can_list_products(): void
    {
        $this->createProduct();
        $this->createProduct();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_product(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'code' => 'SERV001',
            'name' => 'Servicio de Asesoría',
            'type' => 'service',
            'unit_price' => 150.00,
            'tax_code' => '2',
            'tax_percentage_code' => '2',
            'tax_rate' => 12,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'main_code' => 'SERV001',
            'name' => 'Servicio de Asesoría',
        ]);
    }

    public function test_product_scoped_to_tenant(): void
    {
        $product = $this->createProduct();

        $second = $this->createSecondTenant();
        Sanctum::actingAs($second['user']);
        config(['app.tenant_id' => $second['tenant']->id]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        // Global scope makes the product invisible (404), not forbidden (403)
        $response->assertNotFound();
    }
}
