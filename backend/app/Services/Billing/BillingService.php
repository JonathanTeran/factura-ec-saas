<?php

namespace App\Services\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\PaymentException;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Notifications\BankTransferPendingNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\SubscriptionCreatedNotification;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        private PaymentGatewayService $gateway,
    ) {}

    /**
     * Crear nueva suscripción para un tenant.
     */
    public function createSubscription(
        Tenant $tenant,
        Plan $plan,
        string $billingCycle,
        array $paymentData,
        ?string $couponCode = null
    ): Subscription {
        return DB::transaction(function () use ($tenant, $plan, $billingCycle, $paymentData, $couponCode) {
            // Cancelar suscripción anterior si existe
            $tenant->activeSubscription?->cancel('Upgrade a nuevo plan');

            // Validar y aplicar cupón
            $coupon = null;
            $discountAmount = 0;

            if ($couponCode) {
                $coupon = Coupon::findByCode($couponCode);
                if ($coupon && $coupon->canBeUsedByTenant($tenant->id)) {
                    $price = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
                    $discountAmount = $coupon->calculateDiscount($price);
                    $coupon->incrementUses();
                }
            }

            // Calcular precio final
            $price = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $finalPrice = max(0, $price - $discountAmount);

            $paymentMethod = $paymentData['payment_method'] ?? PaymentMethod::BANK_TRANSFER->value;
            $isBankTransfer = $paymentMethod === PaymentMethod::BANK_TRANSFER->value || $paymentMethod === 'transfer';

            // Determine subscription status
            if ($plan->trial_days > 0) {
                $status = SubscriptionStatus::TRIALING;
            } elseif ($isBankTransfer && $finalPrice > 0) {
                $status = SubscriptionStatus::INCOMPLETE;
            } else {
                $status = SubscriptionStatus::ACTIVE;
            }

            // Crear suscripción
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'coupon_code' => $coupon?->code,
                'status' => $status,
                'billing_cycle' => $billingCycle,
                'amount' => $finalPrice,
                'discount_percent' => $coupon ? $coupon->discount_value : 0,
                'currency' => 'USD',
                'starts_at' => now(),
                'ends_at' => $this->calculateEndDate($billingCycle),
                'trial_ends_at' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
                'auto_renew' => true,
                'payment_method' => $paymentMethod,
            ]);

            // Procesar pago si no hay trial y hay monto
            if ($plan->trial_days === 0 && $finalPrice > 0) {
                $this->processPayment($subscription, $paymentData);
            }

            // Actualizar plan actual del tenant
            $tenant->update(['current_plan_id' => $plan->id]);

            // Notificar
            if ($tenant->owner) {
                $tenant->owner->notify(new SubscriptionCreatedNotification($subscription));
            }

            return $subscription;
        });
    }

    /**
     * Procesar pago para una suscripción.
     * Bank transfers create a PENDING payment and notify admin.
     */
    public function processPayment(Subscription $subscription, array $paymentData): Payment
    {
        $paymentMethod = $paymentData['payment_method'] ?? PaymentMethod::BANK_TRANSFER->value;
        $isBankTransfer = $paymentMethod === PaymentMethod::BANK_TRANSFER->value || $paymentMethod === 'transfer';

        $payment = Payment::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::PENDING,
            'payment_method' => $paymentMethod,
            'amount' => $subscription->amount,
            'tax_amount' => $this->calculateTax($subscription->amount),
            'total_amount' => $subscription->amount + $this->calculateTax($subscription->amount),
            'currency' => $subscription->currency,
            'transfer_receipt_path' => $paymentData['transfer_receipt_path'] ?? null,
            'transfer_reference' => $paymentData['transfer_reference'] ?? null,
            'billing_name' => $paymentData['billing_name'] ?? null,
            'billing_email' => $paymentData['billing_email'] ?? null,
            'billing_identification' => $paymentData['billing_identification'] ?? null,
            'billing_address' => $paymentData['billing_address'] ?? null,
            'billing_phone' => $paymentData['billing_phone'] ?? null,
            'description' => "Suscripción {$subscription->plan->name} - " . ($isBankTransfer ? 'Transferencia bancaria' : $paymentMethod),
        ]);

        if ($isBankTransfer) {
            // Bank transfer: leave as PENDING, notify admins
            $this->notifyAdminsOfPendingTransfer($payment);
        } else {
            // For any non-transfer method, process through gateway
            try {
                $result = $this->gateway->charge([
                    'amount' => $payment->total_amount,
                    'currency' => $payment->currency,
                    'payment_method' => $paymentMethod,
                    'description' => "Suscripción {$subscription->plan->name}",
                    'metadata' => [
                        'tenant_id' => $subscription->tenant_id,
                        'subscription_id' => $subscription->id,
                        'payment_id' => $payment->id,
                    ],
                ]);

                $payment->markAsCompleted($result['transaction_id'], $result);

                $subscription->update([
                    'last_payment_at' => now(),
                    'next_payment_at' => $this->calculateEndDate($subscription->billing_cycle),
                    'failed_payments_count' => 0,
                ]);

                if ($subscription->tenant->owner) {
                    $subscription->tenant->owner->notify(new PaymentReceivedNotification($payment));
                }
            } catch (\Exception $e) {
                $payment->markAsFailed($e->getMessage());
                $subscription->increment('failed_payments_count');

                if ($subscription->failed_payments_count >= 3) {
                    $subscription->markAsPastDue();
                }

                throw new PaymentException("Error procesando pago: {$e->getMessage()}", previous: $e);
            }
        }

        return $payment;
    }

    /**
     * Renovar suscripción.
     */
    public function renewSubscription(Subscription $subscription, array $paymentData): Subscription
    {
        $this->processPayment($subscription, $paymentData);
        $subscription->renew();

        return $subscription;
    }

    /**
     * Cancelar suscripción.
     */
    public function cancelSubscription(Subscription $subscription, ?string $reason = null): void
    {
        if ($subscription->gateway_subscription_id) {
            $this->gateway->cancelSubscription($subscription->gateway_subscription_id);
        }

        $subscription->cancel($reason);
    }

    /**
     * Cambiar de plan (upgrade/downgrade).
     */
    public function changePlan(Subscription $subscription, Plan $newPlan, string $billingCycle): Subscription
    {
        $tenant = $subscription->tenant;

        $newPrice = $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;

        $subscription->update([
            'plan_id' => $newPlan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $newPrice,
        ]);

        $tenant->update(['current_plan_id' => $newPlan->id]);

        return $subscription;
    }

    /**
     * Verificar límites del plan.
     */
    public function checkLimit(Tenant $tenant, string $resource): array
    {
        $plan = $tenant->currentPlan;

        if (!$plan) {
            return ['allowed' => false, 'message' => 'Sin plan activo', 'limit' => 0, 'used' => 0];
        }

        return match ($resource) {
            'documents' => $this->checkDocumentLimit($tenant, $plan),
            'users' => $this->checkUserLimit($tenant, $plan),
            'companies' => $this->checkCompanyLimit($tenant, $plan),
            default => ['allowed' => true, 'message' => 'OK', 'limit' => -1, 'used' => 0],
        };
    }

    private function notifyAdminsOfPendingTransfer(Payment $payment): void
    {
        $admins = User::withoutGlobalScopes()->where('role', \App\Enums\UserRole::SUPER_ADMIN)->get();

        foreach ($admins as $admin) {
            $admin->notify(new BankTransferPendingNotification($payment));
        }
    }

    private function checkDocumentLimit(Tenant $tenant, Plan $plan): array
    {
        if ($plan->max_documents_per_month === -1) {
            return ['allowed' => true, 'message' => 'Ilimitado', 'limit' => -1, 'used' => 0];
        }

        $used = $tenant->documentsThisMonth()->count();
        $allowed = $used < $plan->max_documents_per_month;

        return [
            'allowed' => $allowed,
            'message' => $allowed ? 'OK' : 'Límite de documentos alcanzado',
            'limit' => $plan->max_documents_per_month,
            'used' => $used,
        ];
    }

    private function checkUserLimit(Tenant $tenant, Plan $plan): array
    {
        if ($plan->max_users === -1) {
            return ['allowed' => true, 'message' => 'Ilimitado', 'limit' => -1, 'used' => 0];
        }

        $used = $tenant->users()->count();
        $allowed = $used < $plan->max_users;

        return [
            'allowed' => $allowed,
            'message' => $allowed ? 'OK' : 'Límite de usuarios alcanzado',
            'limit' => $plan->max_users,
            'used' => $used,
        ];
    }

    private function checkCompanyLimit(Tenant $tenant, Plan $plan): array
    {
        if ($plan->max_companies === -1) {
            return ['allowed' => true, 'message' => 'Ilimitado', 'limit' => -1, 'used' => 0];
        }

        $used = $tenant->companies()->count();
        $allowed = $used < $plan->max_companies;

        return [
            'allowed' => $allowed,
            'message' => $allowed ? 'OK' : 'Límite de RUCs alcanzado',
            'limit' => $plan->max_companies,
            'used' => $used,
        ];
    }

    private function calculateEndDate(string $billingCycle): \DateTime
    {
        return match ($billingCycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    private function calculateTax(float $amount): float
    {
        // IVA Ecuador 15%
        return round($amount * 0.15, 2);
    }
}
