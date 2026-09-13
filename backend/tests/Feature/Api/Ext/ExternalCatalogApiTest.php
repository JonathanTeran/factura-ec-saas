<?php

namespace Tests\Feature\Api\Ext;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\UsesApiKeys;

/** Clientes, productos y catálogos por la API de integración. */
class ExternalCatalogApiTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase, UsesApiKeys;

    protected string $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->enableApiAccess();
        [, $this->key] = $this->makeApiKey();
    }

    public function test_customers_list_lookup_create_and_update(): void
    {
        $existing = $this->createCustomer(['identification' => '1790012345001']);

        $this->extGet('/customers', $this->key)->assertOk()->assertJsonCount(1, 'data');

        $this->extGet('/customers/lookup?identification=1790012345001', $this->key)
            ->assertOk()
            ->assertJsonPath('data.customer.id', $existing->id);

        $this->extGet('/customers/lookup?identification=0000000000', $this->key)
            ->assertStatus(404)
            ->assertJsonPath('error', 'not_found');

        $created = $this->extPost('/customers', $this->key, [
            'identification_type' => '05',
            'identification_number' => '1710034065',
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
        ])->assertStatus(201)->assertJsonPath('data.customer.identification_number', '1710034065');

        $id = $created->json('data.customer.id');

        $this->extPatch('/customers/'.$id, $this->key, [
            'identification_type' => '05',
            'identification_number' => '1710034065',
            'name' => 'Ana Pérez Torres',
        ])->assertOk()->assertJsonPath('data.customer.name', 'Ana Pérez Torres');

        $this->extGet('/customers/'.$id, $this->key)->assertOk()->assertJsonPath('data.customer.name', 'Ana Pérez Torres');
    }

    public function test_products_create_with_sku_and_update(): void
    {
        $created = $this->extPost('/products', $this->key, [
            'code' => 'CAM-001',
            'sku' => 'SKU-CAM-001',
            'name' => 'Camiseta',
            'type' => 'product',
            'unit_price' => 12.5,
            'tax_code' => '2',
            'tax_percentage_code' => '4',
            'tax_rate' => 15,
        ])->assertStatus(201)
            ->assertJsonPath('data.product.code', 'CAM-001')
            ->assertJsonPath('data.product.sku', 'SKU-CAM-001');

        $this->assertDatabaseHas('products', ['main_code' => 'CAM-001', 'aux_code' => 'SKU-CAM-001']);

        $id = $created->json('data.product.id');

        $this->extPatch('/products/'.$id, $this->key, [
            'code' => 'CAM-002',
            'name' => 'Camiseta premium',
            'type' => 'product',
            'unit_price' => 15,
        ])->assertOk()->assertJsonPath('data.product.code', 'CAM-002');

        $this->extGet('/products', $this->key)->assertOk()->assertJsonCount(1, 'data');
        $this->extGet('/products/'.$id, $this->key)->assertOk()->assertJsonPath('data.product.name', 'Camiseta premium');
    }

    public function test_all_catalogs_respond(): void
    {
        foreach (['identification-types', 'document-types', 'payment-methods', 'tax-rates', 'retention-codes'] as $catalog) {
            $this->extGet('/catalogs/'.$catalog, $this->key)->assertOk()->assertJsonPath('success', true);
        }

        $this->extGet('/catalogs/document-types', $this->key)
            ->assertJsonPath('data.document_types.0.code', '01')
            ->assertJsonPath('data.document_types.0.sri_code', '01');
    }
}
