<?php

namespace App\Services\Billing;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Billing\Coupon;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Billing\ReferralCommission;
use App\Models\Billing\Subscription;
use Illuminate\Support\Carbon;

/**
 * Activa el plan pagado por un pago ya confirmado por la pasarela (PayPal).
 *
 * - Mismo plan y ciclo que la suscripción vigente: renovación; el periodo se
 *   extiende desde su fin actual, así pagar antes de tiempo no quita días.
 * - Otro plan, otro ciclo o sin suscripción: suscripción nueva desde hoy y la
 *   anterior queda reemplazada (cancelada y cerrada hoy).
 *
 * Debe llamarse dentro de una transacción, una sola vez por pago.
 */
class SubscriptionActivator
{
    public function activate(Payment $payment, Plan $plan, string $billingCycle, ?string $couponCode = null): Subscription
    {
        $tenant = $payment->tenant;
        $billingCycle = $billingCycle === 'yearly' ? 'yearly' : 'monthly';
        $now = now();
        $current = $tenant->activeSubscription()->first();
        $coupon = $couponCode ? Coupon::findByCode($couponCode) : null;

        $isRenewal = $current
            && (int) $current->plan_id === (int) $plan->id
            && $current->billing_cycle === $billingCycle
            && $current->status !== SubscriptionStatus::TRIALING;

        if ($isRenewal) {
            $from = $current->ends_at && $current->ends_at->isFuture() ? $current->ends_at : $now;
            $endsAt = $this->periodEnd($from, $billingCycle);

            $current->update([
                'status' => SubscriptionStatus::ACTIVE,
                'ends_at' => $endsAt,
                'canceled_at' => null,
                'cancellation_reason' => null,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method?->value,
                // Así el límite de usos por tenant del cupón también cuenta renovaciones.
                'coupon_code' => $coupon?->code ?? $current->coupon_code,
                'last_payment_at' => $now,
                'next_payment_at' => $endsAt,
                'failed_payments_count' => 0,
            ]);

            $subscription = $current;
        } else {
            if ($current) {
                $current->cancel("Reemplazada por el plan {$plan->name} (pago {$payment->invoice_number})");
                $current->update(['ends_at' => $now]);
            }

            $endsAt = $this->periodEnd($now, $billingCycle);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'coupon_code' => $coupon?->code,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_cycle' => $billingCycle,
                'amount' => $payment->amount,
                'discount_percent' => $coupon && $coupon->discount_type === 'percentage' ? $coupon->discount_value : 0,
                'currency' => $payment->currency ?: 'USD',
                'starts_at' => $now,
                'ends_at' => $endsAt,
                'payment_method' => $payment->payment_method?->value,
                'last_payment_at' => $now,
                'next_payment_at' => $endsAt,
                'failed_payments_count' => 0,
            ]);
        }

        $payment->update(['subscription_id' => $subscription->id]);

        $tenant->update([
            'status' => TenantStatus::ACTIVE,
            'subscription_status' => SubscriptionStatus::ACTIVE,
        ]);
        $tenant->syncPlanLimits($plan);

        $coupon?->incrementUses();

        if (config('billing.referral.enabled', true)) {
            ReferralCommission::createFromPayment($payment, (float) config('billing.referral.commission_percentage', 10));
        }

        return $subscription->fresh(['plan']);
    }

    private function periodEnd(Carbon $from, string $billingCycle): Carbon
    {
        return $billingCycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }
}
