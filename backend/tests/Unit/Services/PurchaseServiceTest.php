<?php

namespace Tests\Unit\Services;

use App\Enums\PurchaseStatus;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Purchase\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $service;
    private Tenant $tenant;
    private User $user;
    private Supplier $supplier;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PurchaseService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->company = Company::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'identification_type' => '04',
            'identification' => '1790011674001',
            'business_name' => 'Proveedor Test S.A.',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_create_purchase_with_items(): void
    {
        $purchase = $this->service
            ->forTenant($this->tenant)
            ->createPurchase([
                'company_id' => $this->company->id,
                'supplier_id' => $this->supplier->id,
                'document_type' => '01',
                'supplier_document_number' => '001-001-000000001',
                'supplier_authorization' => '1234567890',
                'issue_date' => now()->format('Y-m-d'),
                'created_by' => $this->user->id,
            ], [
                [
                    'description' => 'Producto de prueba',
                    'quantity' => 10,
                    'unit_price' => 50.00,
                    'discount' => 0,
                    'tax_rate' => 15,
                ],
            ]);

        $this->assertNotNull($purchase);
        $this->assertDatabaseHas('purchases', [
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'supplier_document_number' => '001-001-000000001',
        ]);
        $this->assertEquals(1, $purchase->items->count());
        $this->assertEquals(500.00, (float) $purchase->items->first()->subtotal);
    }

    public function test_purchase_calculates_totals_correctly(): void
    {
        $purchase = $this->service
            ->forTenant($this->tenant)
            ->createPurchase([
                'company_id' => $this->company->id,
                'supplier_id' => $this->supplier->id,
                'document_type' => '01',
                'supplier_document_number' => '001-001-000000002',
                'issue_date' => now()->format('Y-m-d'),
                'created_by' => $this->user->id,
            ], [
                [
                    'description' => 'Item 1',
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'tax_rate' => 15,
                    'tax_percentage_code' => '4',
                ],
                [
                    'description' => 'Item 2',
                    'quantity' => 5,
                    'unit_price' => 20.00,
                    'tax_rate' => 0,
                    'tax_percentage_code' => '0',
                ],
            ]);

        $purchase->refresh();

        // Item 1: subtotal=200, tax=30, total=230
        // Item 2: subtotal=100, tax=0, total=100
        $this->assertEquals(330.00, (float) $purchase->total);
    }

    public function test_void_purchase_updates_supplier_stats(): void
    {
        $purchase = $this->service
            ->forTenant($this->tenant)
            ->createPurchase([
                'company_id' => $this->company->id,
                'supplier_id' => $this->supplier->id,
                'document_type' => '01',
                'supplier_document_number' => '001-001-000000003',
                'issue_date' => now()->format('Y-m-d'),
                'created_by' => $this->user->id,
            ], [
                [
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'tax_rate' => 15,
                ],
            ]);

        $this->supplier->refresh();
        $initialTotal = (float) $this->supplier->total_purchased;

        $this->service
            ->forTenant($this->tenant)
            ->voidPurchase($purchase);

        $purchase->refresh();
        $this->supplier->refresh();

        $this->assertEquals(PurchaseStatus::VOIDED, $purchase->status);
        $this->assertEquals(0, (float) $this->supplier->total_purchased);
    }

    public function test_ats_compras_returns_correct_format(): void
    {
        $purchase = $this->service
            ->forTenant($this->tenant)
            ->createPurchase([
                'company_id' => $this->company->id,
                'supplier_id' => $this->supplier->id,
                'document_type' => '01',
                'supplier_document_number' => '001-002-000000001',
                'supplier_authorization' => '9876543210',
                'issue_date' => now()->format('Y-m-d'),
                'created_by' => $this->user->id,
            ], [
                [
                    'description' => 'ATS Test',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'tax_rate' => 15,
                ],
            ]);

        $data = $this->service
            ->forTenant($this->tenant)
            ->getATSCompras(now()->startOfMonth(), now()->endOfMonth());

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('tpIdProv', $data[0]);
        $this->assertArrayHasKey('idProv', $data[0]);
        $this->assertArrayHasKey('autorizacion', $data[0]);
        $this->assertEquals('1790011674001', $data[0]['idProv']);
    }

    public function test_get_top_suppliers(): void
    {
        $supplier2 = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'identification_type' => '04',
            'identification' => '1790011674002',
            'business_name' => 'Proveedor 2 S.A.',
            'is_active' => true,
        ]);

        // Purchase from supplier 1 (higher amount)
        $this->service->forTenant($this->tenant)->createPurchase([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'document_type' => '01',
            'supplier_document_number' => '001-001-000000010',
            'issue_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ], [['description' => 'Big purchase', 'quantity' => 10, 'unit_price' => 100, 'tax_rate' => 0, 'tax_percentage_code' => '0']]);

        // Purchase from supplier 2 (lower amount)
        $this->service->forTenant($this->tenant)->createPurchase([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier2->id,
            'document_type' => '01',
            'supplier_document_number' => '001-001-000000011',
            'issue_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ], [['description' => 'Small purchase', 'quantity' => 1, 'unit_price' => 50, 'tax_rate' => 0, 'tax_percentage_code' => '0']]);

        $topSuppliers = $this->service
            ->forTenant($this->tenant)
            ->getTopSuppliers(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(2, $topSuppliers);
        $this->assertEquals($this->supplier->id, $topSuppliers[0]['id']);
    }
}
