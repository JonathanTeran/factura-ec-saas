<?php

namespace Tests\Feature\Api;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Company $company;
    private Branch $branch;
    private EmissionPoint $emissionPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'has_pos' => true,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
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

        Sanctum::actingAs($this->user);
    }

    public function test_can_open_pos_session(): void
    {
        $response = $this->postJson('/api/v1/pos/open-session', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'opening_amount' => 100.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pos_sessions', [
            'tenant_id' => $this->tenant->id,
            'status' => 'open',
        ]);
    }

    public function test_can_get_active_session(): void
    {
        $this->postJson('/api/v1/pos/open-session', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $response = $this->getJson('/api/v1/pos/active-session');

        $response->assertOk()
            ->assertJsonPath('data.session.status', 'open');
    }

    public function test_can_create_transaction(): void
    {
        $sessionResponse = $this->postJson('/api/v1/pos/open-session', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $sessionId = $sessionResponse->json('data.session.id');

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_price' => 25.00,
            'tax_rate' => 15,
            'track_inventory' => false,
        ]);

        $response = $this->postJson("/api/v1/pos/sessions/{$sessionId}/transactions", [
            'payment_method' => 'cash',
            'amount_received' => 50.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 25.00,
                    'tax_rate' => 15,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_can_close_session(): void
    {
        $sessionResponse = $this->postJson('/api/v1/pos/open-session', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'opening_amount' => 50.00,
        ]);

        $sessionId = $sessionResponse->json('data.session.id');

        $response = $this->postJson("/api/v1/pos/sessions/{$sessionId}/close", [
            'closing_amount' => 50.00,
            'closing_notes' => 'Test close',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.session.status', 'closed');
    }

    public function test_can_list_sessions(): void
    {
        $response = $this->getJson('/api/v1/pos/sessions');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_pos_requires_feature_flag(): void
    {
        $tenantWithoutPos = Tenant::factory()->create(['has_pos' => false]);
        $userWithoutPos = User::factory()->create([
            'tenant_id' => $tenantWithoutPos->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($userWithoutPos);

        $response = $this->postJson('/api/v1/pos/open-session', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $response->assertStatus(403);
    }
}
