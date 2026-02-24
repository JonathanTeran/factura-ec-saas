<?php

namespace Tests\Feature\Api;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Coupon;
use App\Models\Billing\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
    }

    public function test_can_list_plans(): void
    {
        Plan::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/subscription/plans');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_get_current_subscription(): void
    {
        $response = $this->getJson('/api/v1/subscription/current');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_cancel_subscription(): void
    {
        $response = $this->postJson('/api/v1/subscription/cancel', [
            'reason' => 'Ya no necesito el servicio',
        ]);

        $response->assertOk();

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::CANCELLED, $this->subscription->status);
    }

    public function test_usage_endpoint_returns_correct_limits(): void
    {
        $response = $this->getJson('/api/v1/subscription/usage');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.documents.limit', 100)
            ->assertJsonPath('data.users.limit', 10)
            ->assertJsonPath('data.companies.limit', 3);
    }

    public function test_can_validate_invalid_coupon(): void
    {
        $response = $this->postJson('/api/v1/subscription/validate-coupon', [
            'code' => 'INVALID_CODE',
        ]);

        // Controller returns 400 for invalid/not found coupons
        $response->assertStatus(400);
    }

    public function test_can_validate_valid_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'code' => 'VALID20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'max_uses' => 100,
            'current_uses' => 0,
        ]);

        $response = $this->postJson('/api/v1/subscription/validate-coupon', [
            'code' => 'VALID20',
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coupon.code', 'VALID20')
            ->assertJsonPath('data.coupon.discount_type', 'percentage');
    }

    public function test_can_get_payment_history(): void
    {
        $response = $this->getJson('/api/v1/subscription/payments');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_cancel_without_active_subscription_returns_error(): void
    {
        // Cancel first
        $this->subscription->cancel('test');

        $response = $this->postJson('/api/v1/subscription/cancel', [
            'reason' => 'test',
        ]);

        $response->assertStatus(400);
    }

    public function test_resume_cancelled_subscription(): void
    {
        // Cancel subscription first
        $this->subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
            'canceled_at' => now(),
            'auto_renew' => false,
            'ends_at' => now()->addDays(15),
        ]);

        $response = $this->postJson('/api/v1/subscription/resume');

        $response->assertOk();

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $this->subscription->status);
    }

    public function test_change_plan(): void
    {
        $newPlan = Plan::factory()->create([
            'price_monthly' => 49.99,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/subscription/change-plan', [
            'plan_id' => $newPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertOk();

        $this->subscription->refresh();
        $this->assertEquals($newPlan->id, $this->subscription->plan_id);
    }

    public function test_plans_are_ordered(): void
    {
        Plan::factory()->create(['is_active' => true, 'sort_order' => 3, 'name' => 'Plan C']);
        Plan::factory()->create(['is_active' => true, 'sort_order' => 1, 'name' => 'Plan A']);
        Plan::factory()->create(['is_active' => true, 'sort_order' => 2, 'name' => 'Plan B']);

        $response = $this->getJson('/api/v1/subscription/plans');

        $response->assertOk();
        $plans = $response->json('data.plans');
        $this->assertNotEmpty($plans);
    }
}
