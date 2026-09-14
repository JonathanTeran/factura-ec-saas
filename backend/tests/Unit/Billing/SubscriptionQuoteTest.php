<?php

namespace Tests\Unit\Billing;

use App\Models\Billing\Coupon;
use App\Models\Billing\Plan;
use App\Services\Billing\SubscriptionQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_and_yearly_totals_add_15_percent_vat_in_exact_cents(): void
    {
        $plan = Plan::factory()->create(['price_monthly' => 2.99, 'price_yearly' => 29.90]);

        $monthly = SubscriptionQuote::make($plan, 'monthly', 1);
        $this->assertSame([2.99, 0.45, 3.44], [$monthly->subtotal, $monthly->tax, $monthly->total]);

        $yearly = SubscriptionQuote::make($plan, 'yearly', 1);
        $this->assertSame([29.9, 4.49, 34.39], [$yearly->subtotal, $yearly->tax, $yearly->total]);
        $this->assertSame('34.39', SubscriptionQuote::money($yearly->total));
    }

    public function test_valid_coupon_lowers_the_subtotal_and_invalid_ones_are_ignored(): void
    {
        $plan = Plan::factory()->create(['price_monthly' => 14.99, 'price_yearly' => 149.99]);
        Coupon::factory()->create([
            'code' => 'MITAD',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'max_discount_amount' => null,
            'min_purchase_amount' => null,
            'max_uses' => null,
            'max_uses_per_tenant' => null,
            'applicable_plans' => null,
            'applicable_billing_cycles' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

        $quote = SubscriptionQuote::make($plan, 'monthly', 1, 'MITAD');
        $this->assertSame('MITAD', $quote->coupon?->code);
        $this->assertSame([7.5, 7.49, 1.12, 8.61], [$quote->discount, $quote->subtotal, $quote->tax, $quote->total]);

        $withoutCoupon = SubscriptionQuote::make($plan, 'monthly', 1, 'NO-EXISTE');
        $this->assertNull($withoutCoupon->coupon);
        $this->assertSame(17.24, $withoutCoupon->total);
    }
}
