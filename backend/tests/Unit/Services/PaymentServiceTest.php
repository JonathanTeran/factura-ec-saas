<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Billing\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use App\Services\Payment\PaymentService;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    private PaymentService $paymentService;
    private PaymentGatewayInterface $mockGateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Notification::fake();

        // Mock the NotificationService to prevent admin lookup issues
        $mockNotificationService = $this->createMock(NotificationService::class);
        $this->app->instance(NotificationService::class, $mockNotificationService);

        $this->mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->mockGateway->method('getName')->willReturn('test');

        $this->paymentService = new PaymentService();
        $this->paymentService->setGateway($this->mockGateway);
    }

    public function test_card_payment_creates_record_and_processes(): void
    {
        $result = new PaymentResult(
            success: true,
            gatewayPaymentId: 'pi_test_123',
            gatewayResponse: ['id' => 'pi_test_123'],
        );

        $this->mockGateway->method('processPayment')->willReturn($result);

        $payment = $this->paymentService->processCardPayment(
            $this->subscription,
            'pm_test_token',
            ['name' => 'Test User', 'email' => 'test@test.com'],
        );

        $this->assertEquals(PaymentStatus::COMPLETED, $payment->status);
        $this->assertNotNull($payment->gateway_payment_id);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $this->subscription->status);
    }

    public function test_failed_card_payment_marks_as_failed(): void
    {
        $result = new PaymentResult(
            success: false,
            errorMessage: 'Card declined',
            gatewayResponse: ['error' => 'card_declined'],
        );

        $this->mockGateway->method('processPayment')->willReturn($result);

        $payment = $this->paymentService->processCardPayment(
            $this->subscription,
            'pm_test_bad',
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        $this->assertEquals(PaymentStatus::FAILED, $payment->status);
    }

    public function test_bank_transfer_creates_pending_payment(): void
    {
        $payment = $this->paymentService->createBankTransferPayment(
            $this->subscription,
            'REF-2024-001',
            null,
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertEquals('REF-2024-001', $payment->transfer_reference);
    }

    public function test_approve_bank_transfer_activates_subscription(): void
    {
        $payment = $this->paymentService->createBankTransferPayment(
            $this->subscription,
            'REF-2024-002',
            null,
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        $result = $this->paymentService->approveBankTransfer($payment, $this->user->id, 'Verified');

        $this->assertTrue($result);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::COMPLETED, $payment->status);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $this->subscription->status);
    }

    public function test_reject_bank_transfer_keeps_subscription_inactive(): void
    {
        $this->subscription->update(['status' => SubscriptionStatus::TRIALING]);

        $payment = $this->paymentService->createBankTransferPayment(
            $this->subscription,
            'REF-2024-003',
            null,
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        $result = $this->paymentService->rejectBankTransfer($payment, $this->user->id, 'Invalid reference');

        $this->assertTrue($result);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStatus::TRIALING, $this->subscription->status);
    }

    public function test_card_payment_creates_correct_amounts(): void
    {
        $result = new PaymentResult(
            success: true,
            gatewayPaymentId: 'pi_test_amounts',
            gatewayResponse: ['id' => 'pi_test_amounts'],
        );

        $this->mockGateway->method('processPayment')->willReturn($result);

        $payment = $this->paymentService->processCardPayment(
            $this->subscription,
            'pm_test_token',
            ['name' => 'Test User', 'email' => 'test@test.com'],
        );

        $this->assertGreaterThan(0, $payment->amount);
        $this->assertGreaterThanOrEqual(0, $payment->tax_amount);
        $this->assertGreaterThanOrEqual($payment->amount, $payment->total_amount);
    }

    public function test_approve_already_completed_payment_returns_false(): void
    {
        $payment = $this->paymentService->createBankTransferPayment(
            $this->subscription,
            'REF-2024-004',
            null,
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        // First approval succeeds
        $this->paymentService->approveBankTransfer($payment, $this->user->id, 'OK');

        // Second approval should fail
        $payment->refresh();
        $result = $this->paymentService->approveBankTransfer($payment, $this->user->id, 'Again');
        $this->assertFalse($result);
    }

    public function test_reject_non_pending_payment_returns_false(): void
    {
        $payment = $this->paymentService->createBankTransferPayment(
            $this->subscription,
            'REF-2024-005',
            null,
            ['name' => 'Test', 'email' => 'test@test.com'],
        );

        // Approve first
        $this->paymentService->approveBankTransfer($payment, $this->user->id, 'OK');

        // Try to reject completed payment
        $payment->refresh();
        $result = $this->paymentService->rejectBankTransfer($payment, $this->user->id, 'Too late');
        $this->assertFalse($result);
    }
}
