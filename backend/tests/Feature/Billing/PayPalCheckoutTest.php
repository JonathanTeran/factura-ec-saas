<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\ReferralCommission;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Notifications\AdminEventNotification;
use App\Notifications\PaymentApprovedNotification;
use App\Services\Settings\PayPalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\FakesPayPal;

class PayPalCheckoutTest extends TestCase
{
    use CreatesTestTenant, FakesPayPal, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
        $this->configurePayPal();
        $this->admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'tenant_id' => null, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        PayPalSettings::forget();
        parent::tearDown();
    }

    private function withoutSubscription(): void
    {
        $this->subscription->update(['status' => SubscriptionStatus::EXPIRED, 'ends_at' => now()->subDay()]);
    }

    private function startCheckout(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/v1/subscription/paypal/orders', array_merge([
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'billing_name' => 'JONATHAN TERAN',
            'billing_email' => 'pagos@example.com',
            'billing_identification' => '1207481803001',
        ], $overrides));
    }

    /** Pago PENDING con su orden creada en PayPal (API simulada). */
    private function pendingPayment(string $orderId, ?Plan $plan = null, string $cycle = 'monthly'): Payment
    {
        $this->fakePayPal(['POST /v2/checkout/orders' => $this->paypalOrderCreated($orderId)]);
        $response = $this->startCheckout(['plan_id' => ($plan ?? $this->plan)->id, 'billing_cycle' => $cycle])->assertCreated();

        return Payment::findOrFail($response->json('data.payment_id'));
    }

    private function captureOk(Payment $payment, ?string $amount = null, string $status = 'COMPLETED'): void
    {
        $order = $payment->gateway_payment_id;
        $this->fakePayPal([
            "POST /v2/checkout/orders/{$order}/capture" => Http::response(
                $this->capturedOrderBody($order, "CAP-{$order}", $amount ?? number_format((float) $payment->total_amount, 2, '.', ''), $status, $payment->id),
                201,
            ),
        ]);
    }

    public function test_checkout_options_show_paypal_only_when_enabled_and_configured(): void
    {
        $this->getJson('/api/v1/subscription/checkout-options')
            ->assertOk()
            ->assertJsonPath('data.paypal.enabled', true)
            ->assertJsonPath('data.paypal.sandbox', true)
            ->assertJsonPath('data.bank_transfer.enabled', true)
            ->assertJsonPath('data.tax_rate', 15);

        app(PayPalSettings::class)->setEnabled(false);

        $this->getJson('/api/v1/subscription/checkout-options')->assertJsonPath('data.paypal.enabled', false);
        $this->startCheckout()->assertStatus(422);
        $this->assertSame(0, Payment::where('payment_method', PaymentMethod::PAYPAL->value)->count());
    }

    public function test_create_order_registers_pending_payment_and_sends_exact_amounts_to_paypal(): void
    {
        $this->fakePayPal(['POST /v2/checkout/orders' => $this->paypalOrderCreated('ORDER-1')]);

        $response = $this->startCheckout()
            ->assertCreated()
            ->assertJsonPath('data.order_id', 'ORDER-1')
            ->assertJsonPath('data.approve_url', 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1')
            ->assertJsonPath('data.amounts.subtotal', 14.99)
            ->assertJsonPath('data.amounts.tax', 2.25)
            ->assertJsonPath('data.amounts.total', 17.24);

        $payment = Payment::findOrFail($response->json('data.payment_id'));
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(PaymentMethod::PAYPAL, $payment->payment_method);
        $this->assertSame('paypal', $payment->gateway);
        $this->assertSame('ORDER-1', $payment->gateway_payment_id);
        $this->assertEquals([14.99, 2.25, 17.24], [(float) $payment->amount, (float) $payment->tax_amount, (float) $payment->total_amount]);
        $this->assertNull($payment->subscription_id);
        $this->assertSame($this->plan->id, $payment->metadata['checkout']['plan_id']);

        Http::assertSent(function (Request $request) use ($payment) {
            if (! str_ends_with($request->url(), '/v2/checkout/orders') || $request->method() !== 'POST') {
                return false;
            }

            $unit = $request['purchase_units'][0];
            $context = $request['payment_source']['paypal']['experience_context'];

            return $request['intent'] === 'CAPTURE'
                && $request->hasHeader('PayPal-Request-Id', "facturon-order-{$payment->id}")
                && $unit['custom_id'] === (string) $payment->id
                && $unit['amount']['value'] === '17.24'
                && $unit['amount']['breakdown']['item_total']['value'] === '14.99'
                && $unit['amount']['breakdown']['tax_total']['value'] === '2.25'
                && $unit['items'][0]['category'] === 'DIGITAL_GOODS'
                && $context['shipping_preference'] === 'NO_SHIPPING'
                && str_ends_with($context['return_url'], '/settings/subscription/paypal')
                && str_contains($context['cancel_url'], '/settings/subscription?paypal=cancelled');
        });
    }

    public function test_coupon_discount_is_charged_but_its_use_is_counted_only_after_payment(): void
    {
        $coupon = Coupon::factory()->create([
            'code' => 'PAYPAL20', 'discount_type' => 'percentage', 'discount_value' => 20,
            'max_discount_amount' => null, 'min_purchase_amount' => null, 'max_uses' => 100, 'current_uses' => 0,
            'max_uses_per_tenant' => null, 'applicable_plans' => null, 'applicable_billing_cycles' => null,
            'starts_at' => null, 'expires_at' => null, 'is_active' => true,
        ]);
        $this->fakePayPal(['POST /v2/checkout/orders' => $this->paypalOrderCreated('ORDER-C')]);

        // 14.99 − 3.00 = 11.99 + IVA 1.80 = 13.79
        $response = $this->startCheckout(['coupon_code' => 'PAYPAL20'])->assertCreated()->assertJsonPath('data.amounts.total', 13.79);
        $this->assertSame(0, $coupon->fresh()->current_uses);

        $payment = Payment::findOrFail($response->json('data.payment_id'));
        $this->captureOk($payment);
        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-C/capture')->assertOk();

        $this->assertSame(1, $coupon->fresh()->current_uses);
        $this->assertSame('PAYPAL20', $payment->fresh()->subscription->coupon_code);
    }

    public function test_new_order_is_refused_while_a_transfer_is_in_review(): void
    {
        Payment::create([
            'tenant_id' => $this->tenant->id, 'status' => PaymentStatus::PENDING, 'payment_method' => PaymentMethod::BANK_TRANSFER,
            'amount' => 14.99, 'tax_amount' => 2.25, 'total_amount' => 17.24, 'currency' => 'USD',
        ]);
        $this->fakePayPal();

        $this->startCheckout()->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'en revisión'));
        Http::assertNothingSent();
    }

    public function test_a_new_attempt_cancels_previous_unapproved_paypal_orders(): void
    {
        $first = $this->pendingPayment('ORDER-OLD');
        $second = $this->pendingPayment('ORDER-NEW');

        $this->assertSame(PaymentStatus::CANCELED, $first->fresh()->status);
        $this->assertSame(PaymentStatus::PENDING, $second->fresh()->status);
    }

    public function test_completed_capture_activates_the_plan_for_a_tenant_without_subscription(): void
    {
        $this->withoutSubscription();
        $payment = $this->pendingPayment('ORDER-9');
        $this->captureOk($payment);

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-9/capture')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $payment->refresh();
        $this->assertSame(PaymentStatus::COMPLETED, $payment->status);
        $this->assertSame('CAP-ORDER-9', $payment->transaction_id);
        $this->assertSame('CAP-ORDER-9', $payment->gateway_transaction_id);
        $this->assertNotNull($payment->paid_at);

        $subscription = $payment->subscription;
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertSame('paypal', $subscription->payment_method);
        $this->assertSame($this->plan->id, $subscription->plan_id);
        $this->assertTrue($subscription->ends_at->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));
        $this->assertSame($subscription->id, $this->tenant->activeSubscription()->first()->id);
        $this->assertSame($this->plan->id, $this->tenant->fresh()->current_plan_id);

        Notification::assertSentTo($this->user, PaymentApprovedNotification::class);
        Notification::assertSentTo($this->admin, AdminEventNotification::class, fn ($n) => $n->event === 'paypal.payment_completed');
    }

    public function test_paying_the_same_plan_renews_from_the_current_end_without_losing_days(): void
    {
        $this->subscription->update(['ends_at' => now()->addDays(10)->startOfSecond()]);
        $expectedEnd = $this->subscription->fresh()->ends_at->copy()->addMonth();

        $payment = $this->pendingPayment('ORDER-R');
        $this->captureOk($payment);
        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-R/capture')->assertOk();

        $subscription = $this->subscription->fresh();
        $this->assertSame($subscription->id, $payment->fresh()->subscription_id);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->equalTo($expectedEnd), "ends_at {$subscription->ends_at} != {$expectedEnd}");
        $this->assertSame(1, Subscription::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_paying_another_plan_replaces_the_current_subscription(): void
    {
        $premium = Plan::factory()->create([
            'price_monthly' => 29.99, 'price_yearly' => 299.99, 'max_documents_per_month' => 500,
            'is_active' => true, 'is_contact_sales' => false,
        ]);

        $payment = $this->pendingPayment('ORDER-UP', $premium);
        $this->assertEquals(34.49, (float) $payment->total_amount);
        $this->captureOk($payment);
        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-UP/capture')->assertOk();

        $old = $this->subscription->fresh();
        $this->assertSame(SubscriptionStatus::CANCELLED, $old->status);
        $this->assertTrue($old->ends_at->lessThanOrEqualTo(now()));

        $new = $payment->fresh()->subscription;
        $this->assertSame($premium->id, $new->plan_id);
        $this->assertSame($new->id, $this->tenant->activeSubscription()->first()->id);
        $this->assertSame(500, $this->tenant->fresh()->max_documents_per_month);
    }

    public function test_capture_is_idempotent_and_never_activates_twice(): void
    {
        $referrer = Tenant::factory()->create(['status' => 'active']);
        $this->tenant->update(['referred_by_tenant_id' => $referrer->id]);
        $this->withoutSubscription();
        $payment = $this->pendingPayment('ORDER-I');

        $captures = 0;
        $this->fakePayPal([
            'POST /v2/checkout/orders/ORDER-I/capture' => function () use (&$captures, $payment) {
                $captures++;

                return Http::response($this->capturedOrderBody('ORDER-I', 'CAP-I', '17.24', 'COMPLETED', $payment->id), 201);
            },
        ]);

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-I/capture')->assertOk();
        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-I/capture')->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertSame(1, $captures);
        $this->assertSame(1, ReferralCommission::where('payment_id', $payment->id)->count());
        $this->assertSame(1, Subscription::where('tenant_id', $this->tenant->id)->where('status', SubscriptionStatus::ACTIVE)->count());
    }

    public function test_pending_capture_stays_in_review_and_blocks_new_payments(): void
    {
        $this->withoutSubscription();
        $payment = $this->pendingPayment('ORDER-P');
        $this->captureOk($payment, status: 'PENDING');

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-P/capture')
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'processing');

        $this->assertSame(PaymentStatus::PROCESSING, $payment->fresh()->status);
        $this->assertNull($this->tenant->activeSubscription()->first());

        $this->getJson('/api/v1/subscription/current')->assertJsonPath('data.pending_payment.payment_method', 'paypal');
        $this->startCheckout()->assertStatus(422);
    }

    public function test_a_capture_for_a_different_amount_is_flagged_and_not_activated(): void
    {
        $this->withoutSubscription();
        $payment = $this->pendingPayment('ORDER-M');
        $this->captureOk($payment, amount: '1.00');

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-M/capture')->assertStatus(202);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PROCESSING, $payment->status);
        $this->assertNull($payment->subscription_id);
        $this->assertNull($this->tenant->activeSubscription()->first());
        Notification::assertSentTo($this->admin, AdminEventNotification::class, fn ($n) => $n->event === 'paypal.review_required');
    }

    public function test_an_order_already_captured_is_recovered_from_paypal(): void
    {
        $this->withoutSubscription();
        $payment = $this->pendingPayment('ORDER-A');
        $this->fakePayPal([
            'POST /v2/checkout/orders/ORDER-A/capture' => $this->paypalError(422, 'ORDER_ALREADY_CAPTURED'),
            'GET /v2/checkout/orders/ORDER-A' => Http::response($this->capturedOrderBody('ORDER-A', 'CAP-A', '17.24', 'COMPLETED', $payment->id)),
        ]);

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-A/capture')->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertSame('CAP-A', $payment->fresh()->gateway_transaction_id);
    }

    public function test_declined_instrument_marks_the_attempt_failed_with_a_retry_message(): void
    {
        $payment = $this->pendingPayment('ORDER-D');
        $this->fakePayPal(['POST /v2/checkout/orders/ORDER-D/capture' => $this->paypalError(422, 'INSTRUMENT_DECLINED')]);

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-D/capture')
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'rechazó el medio de pago'));

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertSame('ORDER-D', $payment->fresh()->gateway_payment_id);
    }

    public function test_orders_of_other_tenants_cannot_be_captured_or_cancelled(): void
    {
        $other = Tenant::factory()->create(['status' => 'active']);
        Payment::create([
            'tenant_id' => $other->id, 'status' => PaymentStatus::PENDING, 'payment_method' => PaymentMethod::PAYPAL,
            'gateway' => 'paypal', 'gateway_payment_id' => 'ORDER-X', 'amount' => 14.99, 'tax_amount' => 2.25,
            'total_amount' => 17.24, 'currency' => 'USD',
        ]);
        $this->fakePayPal();

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-X/capture')->assertNotFound();
        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-X/cancel')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_cancelling_in_paypal_leaves_the_attempt_cancelled_without_charge(): void
    {
        $payment = $this->pendingPayment('ORDER-CAN');

        $this->postJson('/api/v1/subscription/paypal/orders/ORDER-CAN/cancel')->assertOk();

        $this->assertSame(PaymentStatus::CANCELED, $payment->fresh()->status);
    }
}
