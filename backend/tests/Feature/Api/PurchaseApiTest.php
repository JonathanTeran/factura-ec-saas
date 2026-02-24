<?php

namespace Tests\Feature\Api;

use App\Models\Tenant\Company;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Company $company;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->company = Company::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'identification_type' => '04',
            'identification' => '1790011674001',
            'business_name' => 'API Test Supplier',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_suppliers(): void
    {
        $response = $this->getJson('/api/v1/suppliers');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_can_create_supplier(): void
    {
        $response = $this->postJson('/api/v1/suppliers', [
            'identification_type' => '04',
            'identification' => '1790011674999',
            'business_name' => 'New Supplier S.A.',
            'email' => 'supplier@test.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.supplier.business_name', 'New Supplier S.A.');
    }

    public function test_can_create_purchase(): void
    {
        $response = $this->postJson('/api/v1/purchases', [
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'document_type' => '01',
            'supplier_document_number' => '001-001-000000001',
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 5,
                    'unit_price' => 20.00,
                    'tax_rate' => 15,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('purchases', [
            'tenant_id' => $this->tenant->id,
            'supplier_document_number' => '001-001-000000001',
        ]);
    }

    public function test_cannot_create_purchase_without_items(): void
    {
        $response = $this->postJson('/api/v1/purchases', [
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'document_type' => '01',
            'supplier_document_number' => '001-001-000000002',
            'issue_date' => now()->format('Y-m-d'),
            'items' => [],
        ]);

        $response->assertUnprocessable();
    }

    public function test_can_list_purchases(): void
    {
        $response = $this->getJson('/api/v1/purchases');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_cannot_delete_supplier_with_purchases(): void
    {
        // Create a purchase first
        $this->postJson('/api/v1/purchases', [
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'document_type' => '01',
            'supplier_document_number' => '001-001-000000003',
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'tax_rate' => 15],
            ],
        ]);

        $response = $this->deleteJson("/api/v1/suppliers/{$this->supplier->id}");

        $response->assertStatus(400);
    }
}
