<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Billing\BankAccount;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Notifications\BankTransferPendingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class BankTransferPaymentTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
        Storage::fake('public');
    }

    public function test_can_get_bank_accounts(): void
    {
        BankAccount::create([
            'bank_name' => 'Banco Pichincha',
            'account_type' => 'savings',
            'account_number' => '2200123456',
            'holder_name' => 'FacturaEC S.A.',
            'holder_identification' => '1790012345001',
            'instructions' => 'Indicar RUC en la referencia',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        BankAccount::create([
            'bank_name' => 'Banco Guayaquil',
            'account_type' => 'checking',
            'account_number' => '3300654321',
            'holder_name' => 'FacturaEC S.A.',
            'holder_identification' => '1790012345001',
            'instructions' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/subscription/bank-accounts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.bank_accounts');
    }

    public function test_can_subscribe_with_bank_transfer(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 29.99,
        ]);

        $response = $this->postJson('/api/v1/subscription/subscribe-bank-transfer', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'transfer_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'transfer_reference' => 'REF-20260224-001',
            'billing_name' => 'Juan Perez',
            'billing_email' => 'juan@example.com',
            'billing_identification' => '0912345678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        // Verify subscription was created with INCOMPLETE status
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::INCOMPLETE->value,
        ]);

        // Verify payment was created with PENDING status
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::PENDING->value,
            'transfer_reference' => 'REF-20260224-001',
            'billing_name' => 'Juan Perez',
            'billing_email' => 'juan@example.com',
        ]);
    }

    public function test_bank_transfer_requires_receipt(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 29.99,
        ]);

        $response = $this->postJson('/api/v1/subscription/subscribe-bank-transfer', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'transfer_reference' => 'REF-001',
            'billing_name' => 'Test',
            'billing_email' => 'test@test.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['transfer_receipt']);
    }

    public function test_bank_transfer_requires_reference(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 29.99,
        ]);

        $response = $this->postJson('/api/v1/subscription/subscribe-bank-transfer', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'transfer_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'billing_name' => 'Test',
            'billing_email' => 'test@test.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['transfer_reference']);
    }

    public function test_can_check_payment_status(): void
    {
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'transfer_reference' => 'REF-CHECK-001',
        ]);

        $response = $this->getJson("/api/v1/subscription/payment-status/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_cannot_check_other_tenant_payment_status(): void
    {
        // Create payment for current tenant
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
        ]);

        // Switch to second tenant
        $second = $this->createSecondTenant();
        Sanctum::actingAs($second['user']);
        config(['app.tenant_id' => $second['tenant']->id]);

        $response = $this->getJson("/api/v1/subscription/payment-status/{$payment->id}");

        // Should not find the payment because it belongs to another tenant
        $response->assertNotFound();
    }

    public function test_bank_transfer_sends_admin_notification(): void
    {
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'admin@facturaec.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 29.99,
        ]);

        $response = $this->postJson('/api/v1/subscription/subscribe-bank-transfer', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'transfer_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'transfer_reference' => 'REF-NOTIF-001',
            'billing_name' => 'Test User',
            'billing_email' => 'test@test.com',
        ]);

        $response->assertCreated();

        Notification::assertSentTo($admin, BankTransferPendingNotification::class);
    }

    public function test_bank_transfer_with_coupon_applies_discount(): void
    {
        $plan = Plan::factory()->create([
            'trial_days' => 0,
            'price_monthly' => 100.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'TRANSFER20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_uses' => 100,
            'current_uses' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/subscription/subscribe-bank-transfer', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'transfer_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'transfer_reference' => 'REF-COUPON-001',
            'billing_name' => 'Test User',
            'billing_email' => 'test@test.com',
            'coupon_code' => 'TRANSFER20',
        ]);

        $response->assertCreated();

        // Verify subscription amount reflects discount: 100 - 20% = 80
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'amount' => 80.00,
            'coupon_code' => 'TRANSFER20',
        ]);

        // Verify payment amount also reflects discount
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'amount' => 80.00,
        ]);
    }
}
