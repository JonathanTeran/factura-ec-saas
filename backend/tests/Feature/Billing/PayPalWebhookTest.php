<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Billing\Payment;
use App\Models\User;
use App\Notifications\AdminEventNotification;
use App\Services\Settings\PayPalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\FakesPayPal;

class PayPalWebhookTest extends TestCase
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
        $this->subscription->update(['status' => SubscriptionStatus::EXPIRED, 'ends_at' => now()->subDay()]);
    }

    protected function tearDown(): void
    {
        PayPalSettings::forget();
        parent::tearDown();
    }

    private function paypalPayment(array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::PAYPAL,
            'gateway' => 'paypal',
            'gateway_payment_id' => 'ORDER-W',
            'amount' => 14.99,
            'tax_amount' => 2.25,
            'total_amount' => 17.24,
            'currency' => 'USD',
            'metadata' => ['checkout' => ['plan_id' => $this->plan->id, 'plan_name' => $this->plan->name, 'billing_cycle' => 'monthly', 'coupon_code' => null]],
        ], $attributes));
    }

    private function sendWebhook(string $rawBody, bool $withSignature = true): TestResponse
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        if ($withSignature) {
            $headers += [
                'HTTP_PAYPAL_AUTH_ALGO' => 'SHA256withRSA',
                'HTTP_PAYPAL_CERT_URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-TEST',
                'HTTP_PAYPAL_TRANSMISSION_ID' => 'tx-123',
                'HTTP_PAYPAL_TRANSMISSION_SIG' => 'firma-de-prueba',
                'HTTP_PAYPAL_TRANSMISSION_TIME' => '2026-09-14T12:00:00Z',
            ];
        }

        return $this->call('POST', '/api/v1/webhooks/paypal', [], [], [], $headers, $rawBody);
    }

    private function verification(string $status): array
    {
        return ['POST /v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => $status])];
    }

    public function test_events_with_an_invalid_signature_are_rejected_without_touching_payments(): void
    {
        $payment = $this->paypalPayment();
        $this->fakePayPal($this->verification('FAILURE'));

        $raw = json_encode(['id' => 'WH-1', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => ['id' => 'ORDER-W']]);
        $this->sendWebhook($raw)->assertStatus(400);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/capture'));
    }

    public function test_events_without_signature_headers_are_rejected_before_calling_paypal(): void
    {
        $this->fakePayPal();

        $this->sendWebhook('{"event_type":"PAYMENT.CAPTURE.COMPLETED"}', withSignature: false)->assertStatus(400);
        Http::assertNothingSent();
    }

    public function test_the_raw_body_is_forwarded_verbatim_for_signature_verification(): void
    {
        $this->paypalPayment(['status' => PaymentStatus::COMPLETED, 'gateway_transaction_id' => 'CAP-V']);
        $this->fakePayPal($this->verification('SUCCESS'));

        // Espacios y "500.00" tal cual: re-serializar el JSON rompería la firma.
        $raw = '{"id": "WH-RAW", "event_type": "PAYMENT.CAPTURE.COMPLETED", "summary": "Payment completed for $ 17.24 USD", "resource": {"id": "CAP-V", "status": "COMPLETED", "amount": {"value": "17.24", "currency_code": "USD"}}}';
        $this->sendWebhook($raw)->assertOk();

        Http::assertSent(function (Request $request) use ($raw) {
            return str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')
                && str_contains($request->body(), '"webhook_event":'.$raw)
                && str_contains($request->body(), '"webhook_id":"WH-TEST-123"')
                && str_contains($request->body(), '"transmission_id":"tx-123"');
        });
    }

    public function test_order_approved_event_captures_and_activates_when_the_customer_never_returned(): void
    {
        $payment = $this->paypalPayment();
        $this->fakePayPal($this->verification('SUCCESS') + [
            'POST /v2/checkout/orders/ORDER-W/capture' => Http::response($this->capturedOrderBody('ORDER-W', 'CAP-W', '17.24', 'COMPLETED', $payment->id), 201),
        ]);

        $raw = json_encode(['id' => 'WH-2', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => ['id' => 'ORDER-W', 'status' => 'APPROVED', 'purchase_units' => [['custom_id' => (string) $payment->id]]]]);
        $this->sendWebhook($raw)->assertOk()->assertJsonPath('received', true);

        $payment->refresh();
        $this->assertSame(PaymentStatus::COMPLETED, $payment->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $payment->subscription->status);
        $this->assertSame($payment->subscription_id, $this->tenant->activeSubscription()->first()->id);
    }

    public function test_capture_completed_event_finishes_a_payment_that_was_in_review(): void
    {
        $payment = $this->paypalPayment(['status' => PaymentStatus::PROCESSING, 'gateway_transaction_id' => 'CAP-P']);
        $this->fakePayPal($this->verification('SUCCESS'));

        $raw = json_encode(['id' => 'WH-3', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => [
            'id' => 'CAP-P', 'status' => 'COMPLETED', 'custom_id' => (string) $payment->id,
            'amount' => ['value' => '17.24', 'currency_code' => 'USD'],
            'supplementary_data' => ['related_ids' => ['order_id' => 'ORDER-W']],
        ]]);
        $this->sendWebhook($raw)->assertOk();

        $this->assertSame(PaymentStatus::COMPLETED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->subscription_id);
    }

    public function test_refund_event_marks_the_payment_refunded_and_alerts_admins(): void
    {
        $payment = $this->paypalPayment(['status' => PaymentStatus::COMPLETED, 'paid_at' => now(), 'gateway_transaction_id' => 'CAP-R']);
        $this->fakePayPal($this->verification('SUCCESS'));

        $raw = json_encode(['id' => 'WH-4', 'event_type' => 'PAYMENT.CAPTURE.REFUNDED', 'resource' => [
            'id' => 'REF-1', 'status' => 'COMPLETED', 'amount' => ['value' => '17.24', 'currency_code' => 'USD'],
            'seller_payable_breakdown' => ['total_refunded_amount' => ['value' => '17.24', 'currency_code' => 'USD']],
            'links' => [
                ['href' => 'https://api.sandbox.paypal.com/v2/payments/refunds/REF-1', 'rel' => 'self', 'method' => 'GET'],
                ['href' => 'https://api.sandbox.paypal.com/v2/payments/captures/CAP-R', 'rel' => 'up', 'method' => 'GET'],
            ],
        ]]);
        $this->sendWebhook($raw)->assertOk();
        $this->sendWebhook($raw)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::REFUNDED, $payment->status);
        $this->assertEquals(17.24, (float) $payment->refund_amount);
        Notification::assertSentToTimes($this->admin, AdminEventNotification::class, 1);
    }

    public function test_webhooks_are_refused_while_paypal_is_not_configured(): void
    {
        app(PayPalSettings::class)->save(['webhook_id' => '']);
        $this->fakePayPal();

        $this->sendWebhook('{"event_type":"PAYMENT.CAPTURE.COMPLETED"}')->assertStatus(503);
        Http::assertNothingSent();
    }
}
