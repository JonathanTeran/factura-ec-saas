<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Exceptions\PaymentException;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\User;
use App\Notifications\BankTransferPendingNotification;
use App\Services\Billing\BillingService;
use App\Services\Billing\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    private BillingService $billingService;
    private PaymentGatewayService $mockGateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();

        $this->mockGateway = $this->createMock(PaymentGatewayService::class);
        $this->billingService = new BillingService($this->mockGateway);
    }

    public function test_create_subscription_with_trial(): void
    {
        $plan = Plan::factory()->create(['trial_days' => 14]);

        $subscription = $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            ['payment_method' => 'credit_card', 'billing_name' => 'Test', 'billing_email' => 'test@test.com'],
        );

        $this->assertEquals(SubscriptionStatus::TRIALING, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertEquals($plan->id, $subscription->plan_id);
    }

    public function test_create_subscription_without_trial_processes_payment(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 14.99,
        ]);

        $this->mockGateway->method('charge')->willReturn([
            'transaction_id' => 'txn_test_123',
            'status' => 'completed',
        ]);

        $subscription = $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            [
                'payment_method' => 'credit_card',
                'card_token' => 'tok_test',
                'billing_name' => 'Test',
                'billing_email' => 'test@test.com',
            ],
        );

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($plan->id, $subscription->plan_id);
    }

    public function test_create_subscription_applies_coupon_discount(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 100.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'TEST20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_uses' => 10,
            'current_uses' => 0,
            'is_active' => true,
        ]);

        $this->mockGateway->method('charge')->willReturn([
            'transaction_id' => 'txn_test_456',
            'status' => 'completed',
        ]);

        $subscription = $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            [
                'payment_method' => 'credit_card',
                'card_token' => 'tok_test',
                'billing_name' => 'Test',
                'billing_email' => 'test@test.com',
            ],
            'TEST20',
        );

        // final_price = amount stored in DB = 100 - 20 = 80
        $this->assertEquals(80.00, $subscription->final_price);
        // discount_amount = price - final_price = 100 - 80 = 20
        $this->assertEquals(20.00, $subscription->discount_amount);
    }

    public function test_cancel_subscription_updates_status(): void
    {
        $this->billingService->cancelSubscription($this->subscription, 'No longer needed');

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::CANCELLED, $this->subscription->status);
    }

    public function test_change_plan_updates_plan_id(): void
    {
        $newPlan = Plan::factory()->create([
            'price_monthly' => 34.99,
            'max_documents_per_month' => -1,
        ]);

        $result = $this->billingService->changePlan($this->subscription, $newPlan, 'monthly');

        $this->assertEquals($newPlan->id, $result->plan_id);
        $this->tenant->refresh();
        $this->assertEquals($newPlan->id, $this->tenant->current_plan_id);
    }

    public function test_check_document_limit_returns_correct_usage(): void
    {
        $result = $this->billingService->checkLimit($this->tenant, 'documents');

        $this->assertTrue($result['allowed']);
        $this->assertEquals(100, $result['limit']);
        $this->assertEquals(0, $result['used']);
    }

    public function test_create_subscription_cancels_previous_active(): void
    {
        $plan = Plan::factory()->create(['trial_days' => 14]);

        $previousSubscription = $this->subscription;

        $newSubscription = $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            ['payment_method' => 'credit_card', 'billing_name' => 'Test', 'billing_email' => 'test@test.com'],
        );

        $previousSubscription->refresh();
        $this->assertEquals(SubscriptionStatus::CANCELLED, $previousSubscription->status);
        $this->assertNotEquals($previousSubscription->id, $newSubscription->id);
    }

    public function test_check_user_limit(): void
    {
        $result = $this->billingService->checkLimit($this->tenant, 'users');

        $this->assertTrue($result['allowed']);
        $this->assertEquals(10, $result['limit']);
    }

    public function test_check_company_limit(): void
    {
        $result = $this->billingService->checkLimit($this->tenant, 'companies');

        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['limit']);
    }

    public function test_renew_subscription(): void
    {
        $this->mockGateway->method('charge')->willReturn([
            'transaction_id' => 'txn_renew_123',
            'status' => 'completed',
        ]);

        $result = $this->billingService->renewSubscription(
            $this->subscription,
            [
                'payment_method' => 'credit_card',
                'card_token' => 'tok_renew',
                'billing_name' => 'Test',
                'billing_email' => 'test@test.com',
            ]
        );

        $this->assertEquals(SubscriptionStatus::ACTIVE, $result->status);
    }

    public function test_create_bank_transfer_subscription_creates_incomplete_status(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 49.99,
        ]);

        $subscription = $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            [
                'payment_method' => 'transfer',
                'transfer_reference' => 'REF-UNIT-001',
                'billing_name' => 'Test Transfer',
                'billing_email' => 'transfer@test.com',
            ],
        );

        $this->assertEquals(SubscriptionStatus::INCOMPLETE, $subscription->status);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_bank_transfer_process_payment_creates_pending_payment(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 29.99,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::INCOMPLETE,
            'billing_cycle' => 'monthly',
            'amount' => 29.99,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'payment_method' => 'transfer',
        ]);

        $payment = $this->billingService->processPayment($subscription, [
            'payment_method' => 'transfer',
            'transfer_reference' => 'REF-UNIT-002',
            'billing_name' => 'Test',
            'billing_email' => 'test@test.com',
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertEquals('REF-UNIT-002', $payment->transfer_reference);

        // Gateway charge should NOT have been called for bank transfer
        $this->mockGateway->expects($this->never())->method('charge');
    }

    public function test_bank_transfer_notifies_admins(): void
    {
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Admin User',
            'email' => 'admin-unit@facturaec.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 39.99,
        ]);

        $this->billingService->createSubscription(
            $this->tenant,
            $plan,
            'monthly',
            [
                'payment_method' => 'transfer',
                'transfer_reference' => 'REF-UNIT-NOTIF',
                'billing_name' => 'Test',
                'billing_email' => 'test@test.com',
            ],
        );

        Notification::assertSentTo($admin, BankTransferPendingNotification::class);
    }
}
