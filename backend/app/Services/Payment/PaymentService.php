<?php

namespace App\Services\Payment;

use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Billing\ReferralCommission;
use App\Models\Tenant\Tenant;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Services\Payment\Gateways\StripeGateway;
use App\Notifications\BankTransferPendingNotification;
use App\Notifications\PaymentApprovedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    protected ?PaymentGatewayInterface $gateway = null;

    public function __construct()
    {
        // Gateway is resolved lazily to avoid requiring SDK in test environments
    }

    protected function getGateway(): PaymentGatewayInterface
    {
        if ($this->gateway === null) {
            $this->gateway = $this->resolveGateway(config('billing.default_gateway', 'stripe'));
        }

        return $this->gateway;
    }

    /**
     * Process a card payment for a subscription.
     */
    public function processCardPayment(
        Subscription $subscription,
        string $paymentMethodToken,
        array $billingInfo = []
    ): Payment {
        return DB::transaction(function () use ($subscription, $paymentMethodToken, $billingInfo) {
            // Create pending payment record
            $payment = $this->createPaymentRecord($subscription, PaymentMethod::CREDIT_CARD, $billingInfo);

            try {
                // Process payment through gateway
                $result = $this->getGateway()->processPayment($subscription, [
                    'payment_method' => $paymentMethodToken,
                    'customer_id' => $subscription->tenant->settings['stripe_customer_id'] ?? null,
                ]);

                if ($result->success) {
                    // Mark payment as completed
                    $payment->markAsCompleted($result->gatewayPaymentId, $result->gatewayResponse);

                    // Activate subscription
                    $this->activateSubscription($subscription);

                    // Create referral commission if applicable
                    $this->createReferralCommission($payment);

                    Log::info('Card payment processed successfully', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscription->id,
                    ]);
                } else {
                    // Mark payment as failed
                    $payment->markAsFailed($result->errorMessage, $result->gatewayResponse);

                    Log::warning('Card payment failed', [
                        'payment_id' => $payment->id,
                        'error' => $result->errorMessage,
                    ]);
                }

                return $payment->fresh();

            } catch (\Exception $e) {
                $payment->markAsFailed($e->getMessage());

                Log::error('Card payment exception', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create a payment record for bank transfer (requires manual approval).
     */
    public function createBankTransferPayment(
        Subscription $subscription,
        string $transferReference,
        ?string $receiptPath = null,
        array $billingInfo = []
    ): Payment {
        return DB::transaction(function () use ($subscription, $transferReference, $receiptPath, $billingInfo) {
            $payment = $this->createPaymentRecord($subscription, PaymentMethod::BANK_TRANSFER, $billingInfo);

            $payment->update([
                'transfer_reference' => $transferReference,
                'transfer_receipt_path' => $receiptPath,
            ]);

            Log::info('Bank transfer payment created (pending approval)', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
            ]);

            // Notify admins about pending bank transfer
            app(NotificationService::class)->notifyAdmins('payment.pending', [
                'payment_id' => $payment->id,
                'tenant_name' => $subscription->tenant->name,
                'amount' => $payment->total_amount,
            ]);

            return $payment;
        });
    }

    /**
     * Approve a pending bank transfer payment.
     */
    public function approveBankTransfer(Payment $payment, int $approvedBy, ?string $notes = null): bool
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            return false;
        }

        if ($payment->payment_method !== PaymentMethod::BANK_TRANSFER) {
            return false;
        }

        return DB::transaction(function () use ($payment, $approvedBy, $notes) {
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'paid_at' => now(),
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            // Activate subscription
            if ($payment->subscription) {
                $this->activateSubscription($payment->subscription);
            }

            // Create referral commission
            $this->createReferralCommission($payment);

            Log::info('Bank transfer approved', [
                'payment_id' => $payment->id,
                'approved_by' => $approvedBy,
            ]);

            // Notify customer about approval
            $owner = $payment->tenant?->owner;
            if ($owner) {
                $owner->notify(new PaymentApprovedNotification($payment));
            }

            return true;
        });
    }

    /**
     * Reject a pending bank transfer payment.
     */
    public function rejectBankTransfer(Payment $payment, int $rejectedBy, string $reason): bool
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            return false;
        }

        $payment->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
            'approved_by' => $rejectedBy,
            'admin_notes' => "Rechazado: {$reason}",
        ]);

        Log::info('Bank transfer rejected', [
            'payment_id' => $payment->id,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        // Notify customer about rejection
        $owner = $payment->tenant?->owner;
        if ($owner) {
            $owner->notify(new PaymentRejectedNotification($payment, $reason));
        }

        return true;
    }

    /**
     * Process a refund.
     */
    public function processRefund(Payment $payment, float $amount, string $reason): bool
    {
        if (!$payment->canRefund()) {
            return false;
        }

        return DB::transaction(function () use ($payment, $amount, $reason) {
            // If it was a card payment, process refund through gateway
            if (in_array($payment->payment_method, [PaymentMethod::CREDIT_CARD, PaymentMethod::DEBIT_CARD])) {
                $result = $this->getGateway()->refund($payment, $amount);

                if ($result->failed()) {
                    Log::error('Refund failed', [
                        'payment_id' => $payment->id,
                        'error' => $result->errorMessage,
                    ]);
                    return false;
                }
            }

            $payment->refund($amount, $reason);

            Log::info('Payment refunded', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Create a payment record.
     */
    protected function createPaymentRecord(
        Subscription $subscription,
        PaymentMethod $method,
        array $billingInfo = []
    ): Payment {
        $taxRate = config('billing.invoice.tax_rate', 12) / 100;
        $amount = $subscription->final_price;
        $taxAmount = round($amount * $taxRate, 2);
        $totalAmount = $amount + $taxAmount;

        return Payment::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
            'status' => PaymentStatus::PENDING,
            'payment_method' => $method,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => config('billing.currency', 'USD'),
            'gateway' => $this->getGateway()->getName(),
            'description' => "Suscripción {$subscription->plan->name} ({$subscription->billing_cycle})",
            'billing_name' => $billingInfo['name'] ?? $subscription->tenant->name,
            'billing_email' => $billingInfo['email'] ?? $subscription->tenant->owner_email,
            'billing_identification' => $billingInfo['identification'] ?? null,
            'billing_address' => $billingInfo['address'] ?? null,
            'billing_phone' => $billingInfo['phone'] ?? null,
        ]);
    }

    /**
     * Activate a subscription after successful payment.
     */
    protected function activateSubscription(Subscription $subscription): void
    {
        $endsAt = $subscription->billing_cycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'ends_at' => $endsAt,
        ]);

        // Update tenant status and sync plan limits
        $subscription->tenant->update([
            'status' => TenantStatus::ACTIVE,
            'subscription_status' => SubscriptionStatus::ACTIVE,
        ]);

        if ($subscription->plan) {
            $subscription->tenant->syncPlanLimits($subscription->plan);
        }
    }

    /**
     * Create referral commission if applicable.
     */
    protected function createReferralCommission(Payment $payment): void
    {
        if (!config('billing.referral.enabled', true)) {
            return;
        }

        $commission = ReferralCommission::createFromPayment(
            $payment,
            config('billing.referral.commission_percentage', 10)
        );

        if ($commission) {
            Log::info('Referral commission created', [
                'commission_id' => $commission->id,
                'payment_id' => $payment->id,
                'amount' => $commission->commission_amount,
            ]);
        }
    }

    /**
     * Resolve the payment gateway based on configuration.
     */
    protected function resolveGateway(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'stripe' => new StripeGateway(),
            // 'paypal' => new PayPalGateway(),
            // 'paymentez' => new PaymentezGateway(),
            default => new StripeGateway(),
        };
    }

    /**
     * Set a specific gateway.
     */
    public function setGateway(PaymentGatewayInterface $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }
}
