<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Pages\PayPalSettings as PayPalSettingsPage;
use App\Models\Billing\Payment;
use App\Models\Billing\PaymentMethodSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Settings\PayPalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\FakesPayPal;

class PayPalAdminTest extends TestCase
{
    use CreatesTestTenant, FakesPayPal, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        PayPalSettings::forget();
        $this->admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'tenant_id' => null, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        PayPalSettings::forget();
        parent::tearDown();
    }

    private function completedPayPalPayment(): Payment
    {
        return Payment::create([
            'tenant_id' => $this->tenant->id, 'status' => PaymentStatus::COMPLETED, 'paid_at' => now(),
            'payment_method' => PaymentMethod::PAYPAL, 'gateway' => 'paypal', 'gateway_payment_id' => 'ORDER-F',
            'gateway_transaction_id' => 'CAP-F', 'transaction_id' => 'CAP-F',
            'amount' => 14.99, 'tax_amount' => 2.25, 'total_amount' => 17.24, 'currency' => 'USD',
        ]);
    }

    public function test_the_migration_adds_paypal_disabled_by_default(): void
    {
        $row = PaymentMethodSetting::where('code', 'paypal')->first();

        $this->assertNotNull($row);
        $this->assertFalse($row->is_enabled);
        $this->assertFalse(app(PayPalSettings::class)->isAvailable());
    }

    public function test_the_secret_is_encrypted_at_rest_and_a_blank_value_keeps_it(): void
    {
        $settings = app(PayPalSettings::class);
        $settings->save(['mode' => 'live', 'client_id' => 'live-client', 'client_secret' => 'super-secreto', 'enabled' => true]);

        $stored = SystemSetting::where('key', 'paypal.client_secret')->value('value');
        $this->assertNotSame('super-secreto', $stored);
        $this->assertStringNotContainsString('super-secreto', (string) $stored);

        $settings->save(['client_secret' => '', 'client_id' => 'live-client-2']);

        $fresh = app(PayPalSettings::class);
        $this->assertSame('super-secreto', $fresh->clientSecret());
        $this->assertSame('live-client-2', $fresh->clientId());
        $this->assertSame('https://api-m.paypal.com', $fresh->apiBaseUrl());
        $this->assertTrue($fresh->isAvailable());
    }

    public function test_env_credentials_are_used_until_the_admin_saves_others(): void
    {
        config(['billing.paypal.client_id' => 'env-client', 'billing.paypal.client_secret' => 'env-secret', 'billing.paypal.mode' => 'sandbox']);
        app(PayPalSettings::class)->setEnabled(true);

        $settings = app(PayPalSettings::class);
        $this->assertSame('env-client', $settings->clientId());
        $this->assertSame('env-secret', $settings->clientSecret());
        $this->assertTrue($settings->isAvailable());
    }

    public function test_admin_page_saves_settings_and_never_sends_the_secret_back(): void
    {
        $this->actingAs($this->admin, 'web')->get('/admin/paypal-settings')->assertOk()->assertSee('Pagos con PayPal');

        Livewire::actingAs($this->admin, 'web')
            ->test(PayPalSettingsPage::class)
            ->assertSet('data.client_secret', null)
            ->fillForm(['enabled' => true, 'mode' => 'sandbox', 'client_id' => 'panel-client', 'client_secret' => 'panel-secret', 'webhook_id' => ''])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.client_secret', null);

        $settings = app(PayPalSettings::class);
        $this->assertTrue($settings->isAvailable());
        $this->assertSame('panel-secret', $settings->clientSecret());
    }

    public function test_paypal_cannot_be_enabled_without_credentials(): void
    {
        Livewire::actingAs($this->admin, 'web')
            ->test(PayPalSettingsPage::class)
            ->fillForm(['enabled' => true, 'mode' => 'sandbox', 'client_id' => '', 'client_secret' => ''])
            ->call('save')
            ->assertNotified('Faltan credenciales');

        $this->assertFalse(app(PayPalSettings::class)->isEnabled());
    }

    public function test_register_webhook_creates_it_or_reuses_the_existing_one(): void
    {
        $this->configurePayPal(overrides: ['webhook_id' => '']);
        $url = app(PayPalSettings::class)->webhookUrl();

        $this->fakePayPal([
            'POST /v1/notifications/webhooks' => Http::response(['name' => 'WEBHOOK_URL_ALREADY_EXISTS', 'message' => 'Webhook URL already exists.'], 400),
            'GET /v1/notifications/webhooks' => Http::response(['webhooks' => [['id' => 'WH-OLD', 'url' => $url, 'event_types' => []]]]),
            'PATCH /v1/notifications/webhooks/WH-OLD' => Http::response(['id' => 'WH-OLD', 'url' => $url]),
        ]);

        Livewire::actingAs($this->admin, 'web')
            ->test(PayPalSettingsPage::class)
            ->callAction('registerWebhook')
            ->assertSet('data.webhook_id', 'WH-OLD');

        $this->assertSame('WH-OLD', app(PayPalSettings::class)->webhookId());
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH'
            && collect($r->data()[0]['value'] ?? [])->pluck('name')->contains('CHECKOUT.ORDER.APPROVED'));
    }

    public function test_admin_refund_returns_the_money_through_paypal(): void
    {
        $this->configurePayPal();
        $payment = $this->completedPayPalPayment();
        $this->fakePayPal([
            'POST /v2/payments/captures/CAP-F/refund' => Http::response(['id' => 'REF-F', 'status' => 'COMPLETED', 'amount' => ['value' => '5.00', 'currency_code' => 'USD']], 201),
        ]);

        $service = app(PaymentService::class);
        $this->assertTrue($service->processRefund($payment, 5.00, 'Ajuste de precio'));

        $payment->refresh();
        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $payment->status);
        $this->assertEquals(5.00, (float) $payment->refund_amount);
        $this->assertSame('REF-F', $payment->gateway_response['refunds'][0]['id']);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/payments/captures/CAP-F/refund')
            && $r['amount']['value'] === '5.00'
            && $r['note_to_payer'] === 'Ajuste de precio');
    }

    public function test_a_refund_rejected_by_paypal_is_not_marked_as_refunded(): void
    {
        $this->configurePayPal();
        $payment = $this->completedPayPalPayment();
        $this->fakePayPal([
            'POST /v2/payments/captures/CAP-F/refund' => $this->paypalError(422, 'CAPTURE_FULLY_REFUNDED', 'The capture has already been fully refunded'),
        ]);

        $service = app(PaymentService::class);
        $this->assertFalse($service->processRefund($payment, 17.24, 'Duplicado'));
        $this->assertNotNull($service->lastError());
        $this->assertSame(PaymentStatus::COMPLETED, $payment->fresh()->status);
    }

    public function test_public_landing_mentions_paypal_only_when_it_is_available(): void
    {
        $this->getJson('/api/v1/public/landing')->assertOk()->assertJsonPath('data.payment_methods.paypal', false);

        $this->configurePayPal();

        $this->getJson('/api/v1/public/landing')->assertJsonPath('data.payment_methods.paypal', true)
            ->assertJsonPath('data.payment_methods.bank_transfer', true);
    }
}
