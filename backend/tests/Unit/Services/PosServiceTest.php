<?php

namespace Tests\Unit\Services;

use App\Enums\PosSessionStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\PosSession;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Pos\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosServiceTest extends TestCase
{
    use RefreshDatabase;

    private PosService $service;
    private Tenant $tenant;
    private User $user;
    private Company $company;
    private Branch $branch;
    private EmissionPoint $emissionPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PosService();
        $this->tenant = Tenant::factory()->create([
            'has_pos' => true,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->company = Company::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->branch = Branch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
        ]);
        $this->emissionPoint = EmissionPoint::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_open_session(): void
    {
        $session = $this->service
            ->forTenant($this->tenant)
            ->openSession([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'emission_point_id' => $this->emissionPoint->id,
                'opening_amount' => 50.00,
            ]);

        $this->assertNotNull($session);
        $this->assertEquals(PosSessionStatus::OPEN, $session->status);
        $this->assertEquals(50.00, (float) $session->opening_amount);
        $this->assertEquals($this->user->id, $session->opened_by);
    }

    public function test_cannot_open_duplicate_session_for_same_emission_point(): void
    {
        $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);
    }

    public function test_can_close_session(): void
    {
        $session = $this->service
            ->forTenant($this->tenant)
            ->openSession([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'emission_point_id' => $this->emissionPoint->id,
                'opening_amount' => 100.00,
            ]);

        $closed = $this->service
            ->forTenant($this->tenant)
            ->closeSession($session, 150.00, 'Cierre normal');

        $this->assertEquals(PosSessionStatus::CLOSED, $closed->status);
        $this->assertEquals(150.00, (float) $closed->closing_amount);
        $this->assertNotNull($closed->closed_at);
    }

    public function test_cannot_close_already_closed_session(): void
    {
        $session = $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $this->service->forTenant($this->tenant)->closeSession($session, 0);

        $this->expectException(\RuntimeException::class);

        $this->service->forTenant($this->tenant)->closeSession($session->fresh(), 0);
    }

    public function test_can_create_transaction(): void
    {
        $session = $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'opening_amount' => 0,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'unit_price' => 10.00,
            'tax_rate' => 15,
            'track_inventory' => true,
            'current_stock' => 100,
        ]);

        $transaction = $this->service->forTenant($this->tenant)->createTransaction($session, [
            'payment_method' => 'cash',
            'amount_received' => 25.00,
        ], [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 10.00,
                'tax_rate' => 15,
            ],
        ]);

        $this->assertNotNull($transaction);
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals('cash', $transaction->payment_method);
        $this->assertEquals(20.00, (float) $transaction->subtotal);
        $this->assertEquals(1, $transaction->items->count());

        // Stock should be decremented
        $product->refresh();
        $this->assertEquals(98, (float) $product->current_stock);

        // Session totals should be updated
        $session->refresh();
        $this->assertEquals(1, $session->total_transactions);
    }

    public function test_void_transaction_restores_inventory(): void
    {
        $session = $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'track_inventory' => true,
            'current_stock' => 50,
            'unit_price' => 10.00,
            'tax_rate' => 15,
        ]);

        $transaction = $this->service->forTenant($this->tenant)->createTransaction($session, [
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10.00, 'tax_rate' => 15],
        ]);

        $product->refresh();
        $this->assertEquals(45, (float) $product->current_stock);

        $this->service->forTenant($this->tenant)->voidTransaction($transaction);

        $product->refresh();
        $this->assertEquals(50, (float) $product->current_stock);

        $transaction->refresh();
        $this->assertEquals('voided', $transaction->status);
    }

    public function test_pos_requires_feature_flag(): void
    {
        $tenantWithoutPos = Tenant::factory()->create([
            'has_pos' => false,
        ]);

        $this->expectException(\App\Exceptions\FeatureNotAvailableException::class);

        $this->service->forTenant($tenantWithoutPos);
    }

    public function test_get_active_session(): void
    {
        $this->assertNull($this->service->forTenant($this->tenant)->getActiveSession());

        $session = $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $active = $this->service->forTenant($this->tenant)->getActiveSession();
        $this->assertNotNull($active);
        $this->assertEquals($session->id, $active->id);
    }

    public function test_transaction_calculates_change(): void
    {
        $session = $this->service->forTenant($this->tenant)->openSession([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $transaction = $this->service->forTenant($this->tenant)->createTransaction($session, [
            'payment_method' => 'cash',
            'amount_received' => 50.00,
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 20.00, 'tax_rate' => 15],
        ]);

        // Total = 20 + 3 (IVA 15%) = 23
        $this->assertEquals(50.00, (float) $transaction->amount_received);
        $this->assertEquals(27.00, (float) $transaction->change_amount);
    }
}
