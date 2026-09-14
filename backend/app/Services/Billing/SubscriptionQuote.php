<?php

namespace App\Services\Billing;

use App\Models\Billing\Coupon;
use App\Models\Billing\Plan;

/**
 * Monto a cobrar por un plan y ciclo: precio − cupón = subtotal, IVA 15 %
 * encima, total en USD. Mismos montos que el pago por transferencia; se
 * calcula en centavos enteros para que PayPal reciba sumas exactas.
 */
final class SubscriptionQuote
{
    public const TAX_RATE = 15;

    private function __construct(
        public readonly Plan $plan,
        public readonly string $billingCycle,
        public readonly float $basePrice,
        public readonly float $discount,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $total,
        public readonly ?Coupon $coupon,
    ) {}

    public static function make(Plan $plan, string $billingCycle, int $tenantId, ?string $couponCode = null): self
    {
        $billingCycle = $billingCycle === 'yearly' ? 'yearly' : 'monthly';
        $baseCents = self::cents($billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly);

        $coupon = null;
        $discountCents = 0;

        if ($couponCode !== null && trim($couponCode) !== '') {
            $candidate = Coupon::findByCode(trim($couponCode));

            if ($candidate
                && $candidate->canBeUsedByTenant($tenantId)
                && $candidate->isApplicableToPlan($plan->id)
                && $candidate->isApplicableToBillingCycle($billingCycle)) {
                $coupon = $candidate;
                $discountCents = min($baseCents, self::cents($candidate->calculateDiscount($baseCents / 100)));
            }
        }

        $subtotalCents = max(0, $baseCents - $discountCents);
        $taxCents = (int) round($subtotalCents * self::TAX_RATE / 100);

        return new self(
            plan: $plan,
            billingCycle: $billingCycle,
            basePrice: $baseCents / 100,
            discount: $discountCents / 100,
            subtotal: $subtotalCents / 100,
            tax: $taxCents / 100,
            total: ($subtotalCents + $taxCents) / 100,
            coupon: $coupon,
        );
    }

    public function cycleLabel(): string
    {
        return $this->billingCycle === 'yearly' ? 'anual' : 'mensual';
    }

    public static function money(float|int|string|null $amount): string
    {
        return number_format(self::cents($amount) / 100, 2, '.', '');
    }

    private static function cents(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
