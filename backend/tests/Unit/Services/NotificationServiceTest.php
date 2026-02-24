<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Notifications\BankTransferPendingNotification;
use App\Notifications\PaymentApprovedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\SubscriptionCreatedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use App\Notifications\SubscriptionRenewedNotification;
use App\Notifications\TrialEndingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();
    }

    public function test_bank_transfer_pending_notification_contains_payment_info(): void
    {
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'transfer_reference' => 'REF-TEST-001',
            'total_amount' => 29.99,
            'currency' => 'USD',
        ]);

        $notification = new BankTransferPendingNotification($payment);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('transferencia', strtolower($mail->subject));

        // Verify the mail message contains expected payment information
        $rendered = $mail->render();
        $renderedLower = strtolower($rendered);

        $this->assertStringContainsString('ref-test-001', $renderedLower);
        $this->assertStringContainsString('29.99', $rendered);
        $this->assertStringContainsString('usd', $renderedLower);
    }

    public function test_bank_transfer_pending_notification_includes_tenant_name(): void
    {
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'transfer_reference' => 'REF-TEST-002',
        ]);

        $notification = new BankTransferPendingNotification($payment);
        $mail = $notification->toMail($this->user);

        $rendered = $mail->render();
        $this->assertStringContainsString($this->tenant->name, $rendered);
    }

    public function test_payment_approved_notification_contains_plan_info(): void
    {
        $payment = Payment::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'total_amount' => 49.99,
            'currency' => 'USD',
            'transaction_id' => 'TXN-APPROVED-001',
        ]);

        $notification = new PaymentApprovedNotification($payment);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('aprobado', strtolower($mail->subject));

        $rendered = $mail->render();
        $renderedLower = strtolower($rendered);

        $this->assertStringContainsString('49.99', $rendered);
        $this->assertStringContainsString($this->plan->name, $rendered);
        $this->assertStringContainsString('activa', $renderedLower);
    }

    public function test_payment_rejected_notification_contains_reason(): void
    {
        $payment = Payment::factory()->failed()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'total_amount' => 14.99,
            'currency' => 'USD',
            'transaction_id' => 'TXN-REJECTED-001',
        ]);

        $reason = 'Comprobante de transferencia no valido';

        $notification = new PaymentRejectedNotification($payment, $reason);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('rechazado', strtolower($mail->subject));

        $rendered = $mail->render();
        $this->assertStringContainsString($reason, $rendered);
        $this->assertStringContainsString('14.99', $rendered);
    }

    public function test_payment_rejected_notification_without_reason(): void
    {
        $payment = Payment::factory()->failed()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'transaction_id' => 'TXN-REJECTED-002',
        ]);

        $notification = new PaymentRejectedNotification($payment);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        // Should still render without errors even without a reason
        $rendered = $mail->render();
        $this->assertStringContainsString($this->user->name, $rendered);
    }

    public function test_subscription_created_notification(): void
    {
        $notification = new SubscriptionCreatedNotification($this->subscription);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString($this->plan->name, $mail->subject);
        $this->assertStringContainsString('suscripcion', strtolower($mail->subject));
    }

    public function test_subscription_created_notification_via_channels(): void
    {
        $notification = new SubscriptionCreatedNotification($this->subscription);
        $channels = $notification->via($this->user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_subscription_cancelled_notification_contains_end_date(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addDays(15),
            'cancellation_reason' => 'Ya no necesito el servicio',
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionCancelledNotification($this->subscription);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('cancelada', strtolower($mail->subject));

        $rendered = $mail->render();
        $renderedLower = strtolower($rendered);

        // Should contain the end date formatted as dd/mm/YYYY
        $expectedDate = $this->subscription->ends_at->format('d/m/Y');
        $this->assertStringContainsString($expectedDate, $rendered);

        // Should contain the cancellation reason
        $this->assertStringContainsString('ya no necesito el servicio', $renderedLower);
    }

    public function test_subscription_cancelled_notification_without_reason(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addDays(10),
            'cancellation_reason' => null,
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionCancelledNotification($this->subscription);
        $mail = $notification->toMail($this->user);

        $rendered = $mail->render();
        $this->assertStringContainsString('No especificado', $rendered);
    }

    public function test_subscription_renewed_notification(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addMonth(),
            'billing_cycle' => 'monthly',
            'amount' => 19.99,
            'currency' => 'USD',
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionRenewedNotification($this->subscription);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('renovada', strtolower($mail->subject));

        $rendered = $mail->render();
        $renderedLower = strtolower($rendered);

        $this->assertStringContainsString('19.99', $rendered);
        $this->assertStringContainsString('mensual', $renderedLower);

        // Should contain the next renewal date
        $expectedDate = $this->subscription->ends_at->format('d/m/Y');
        $this->assertStringContainsString($expectedDate, $rendered);
    }

    public function test_subscription_renewed_notification_yearly_cycle(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addYear(),
            'billing_cycle' => 'yearly',
            'amount' => 199.99,
            'currency' => 'USD',
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionRenewedNotification($this->subscription);
        $mail = $notification->toMail($this->user);

        $rendered = $mail->render();
        $renderedLower = strtolower($rendered);

        $this->assertStringContainsString('199.99', $rendered);
        $this->assertStringContainsString('anual', $renderedLower);
    }

    public function test_trial_ending_notification(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->addDays(3)]);
        $this->tenant->refresh();

        $notification = new TrialEndingNotification($this->tenant, 3);
        $mail = $notification->toMail($this->user);

        $this->assertNotNull($mail->subject);
        $this->assertStringContainsString('3', $mail->subject);
        $this->assertStringContainsString('prueba', strtolower($mail->subject));
    }

    public function test_trial_ending_notification_urgent(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->addDays(1)]);
        $this->tenant->refresh();

        $notification = new TrialEndingNotification($this->tenant, 1);
        $mail = $notification->toMail($this->user);

        // Should contain URGENTE when 3 days or less
        $this->assertStringContainsString('URGENTE', $mail->subject);
    }

    public function test_trial_ending_notification_not_urgent(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->addDays(7)]);
        $this->tenant->refresh();

        $notification = new TrialEndingNotification($this->tenant, 7);
        $mail = $notification->toMail($this->user);

        // Should NOT contain URGENTE when more than 3 days
        $this->assertStringNotContainsString('URGENTE', $mail->subject);
    }

    public function test_trial_ending_notification_to_array(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->addDays(5)]);
        $this->tenant->refresh();

        $notification = new TrialEndingNotification($this->tenant, 5);
        $data = $notification->toArray($this->user);

        $this->assertEquals('trial_ending', $data['type']);
        $this->assertEquals($this->tenant->id, $data['tenant_id']);
        $this->assertEquals(5, $data['days_remaining']);
    }

    public function test_bank_transfer_pending_notification_to_array(): void
    {
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'total_amount' => 29.99,
        ]);

        $notification = new BankTransferPendingNotification($payment);
        $data = $notification->toArray($this->user);

        $this->assertEquals('bank_transfer_pending', $data['type']);
        $this->assertEquals($payment->id, $data['payment_id']);
        $this->assertEquals(29.99, $data['amount']);
    }

    public function test_payment_approved_notification_to_array(): void
    {
        $payment = Payment::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'transaction_id' => 'TXN-ARRAY-001',
            'total_amount' => 39.99,
        ]);

        $notification = new PaymentApprovedNotification($payment);
        $data = $notification->toArray($this->user);

        $this->assertEquals('payment_approved', $data['type']);
        $this->assertEquals($payment->id, $data['payment_id']);
        $this->assertEquals('TXN-ARRAY-001', $data['transaction_id']);
    }

    public function test_payment_rejected_notification_to_array(): void
    {
        $payment = Payment::factory()->failed()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'transaction_id' => 'TXN-REJECT-ARRAY',
        ]);

        $reason = 'Referencia invalida';
        $notification = new PaymentRejectedNotification($payment, $reason);
        $data = $notification->toArray($this->user);

        $this->assertEquals('payment_rejected', $data['type']);
        $this->assertEquals($reason, $data['reason']);
    }

    public function test_subscription_cancelled_notification_to_array(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addDays(15),
            'cancellation_reason' => 'Motivo de prueba',
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionCancelledNotification($this->subscription);
        $data = $notification->toArray($this->user);

        $this->assertEquals('subscription_cancelled', $data['type']);
        $this->assertEquals($this->subscription->id, $data['subscription_id']);
        $this->assertEquals('Motivo de prueba', $data['cancellation_reason']);
    }

    public function test_subscription_renewed_notification_to_array(): void
    {
        $this->subscription->update([
            'ends_at' => now()->addMonth(),
            'amount' => 24.99,
        ]);
        $this->subscription->refresh();

        $notification = new SubscriptionRenewedNotification($this->subscription);
        $data = $notification->toArray($this->user);

        $this->assertEquals('subscription_renewed', $data['type']);
        $this->assertEquals($this->subscription->id, $data['subscription_id']);
        $this->assertEquals(24.99, $data['amount']);
    }

    public function test_all_notifications_use_mail_and_database_channels(): void
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
        ]);

        $notifications = [
            new BankTransferPendingNotification($payment),
            new PaymentApprovedNotification($payment),
            new PaymentRejectedNotification($payment),
            new SubscriptionCreatedNotification($this->subscription),
            new SubscriptionCancelledNotification($this->subscription),
            new SubscriptionRenewedNotification($this->subscription),
            new TrialEndingNotification($this->tenant, 5),
        ];

        foreach ($notifications as $notification) {
            $channels = $notification->via($this->user);
            $this->assertContains('mail', $channels, get_class($notification) . ' should use mail channel');
            $this->assertContains('database', $channels, get_class($notification) . ' should use database channel');
        }
    }
}
