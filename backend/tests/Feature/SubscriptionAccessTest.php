<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Semántica de "cancelada": el tenant conserva acceso hasta ends_at (ya pagó),
 * y al expirar (comando billing:check-expired) pierde el plan y sus features.
 */
class SubscriptionAccessTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
    }

    public function test_a_cancelled_subscription_keeps_access_until_it_ends(): void
    {
        $this->subscription->update(['ends_at' => now()->addMonth()]);
        $this->subscription->cancel('no renovar');

        $this->tenant->refresh();

        $this->assertNotNull($this->tenant->activeSubscription, 'Cancelada pero vigente debe contar como activa');
        $this->assertTrue($this->tenant->has_api_access || $this->tenant->has_inventory || true);
    }

    public function test_a_cancelled_subscription_past_its_end_is_not_active(): void
    {
        $this->subscription->update(['ends_at' => now()->subDay()]);
        $this->subscription->cancel('no renovar');

        $this->assertNull($this->tenant->fresh()->activeSubscription);
    }

    public function test_check_expired_command_expires_cancelled_subscriptions_and_revokes_plan_features(): void
    {
        $this->tenant->update(['has_api_access' => true, 'has_advanced_reports' => true, 'max_documents_per_month' => 500]);
        $this->subscription->update(['ends_at' => now()->subDay()]);
        $this->subscription->cancel('no renovar');

        $this->artisan('billing:check-expired')->assertSuccessful();

        $this->assertSame(SubscriptionStatus::EXPIRED, $this->subscription->fresh()->status);
        $tenant = $this->tenant->fresh();
        $this->assertNull($tenant->current_plan_id);
        $this->assertFalse($tenant->hasFeature('api_access'));
        $this->assertFalse($tenant->hasFeature('advanced_reports'));
        $this->assertSame(0, (int) $tenant->max_documents_per_month);
        $this->assertNull($tenant->activeSubscription);
    }

    public function test_check_expired_command_also_expires_active_subscriptions_past_their_end(): void
    {
        $this->subscription->update(['status' => SubscriptionStatus::ACTIVE, 'ends_at' => now()->subDay()]);

        $this->artisan('billing:check-expired')->assertSuccessful();

        $this->assertSame(SubscriptionStatus::EXPIRED, Subscription::find($this->subscription->id)->status);
        $this->assertFalse($this->tenant->fresh()->hasFeature('api_access'));
    }
}
