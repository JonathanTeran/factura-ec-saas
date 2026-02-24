<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Billing\Payment;
use App\Models\Billing\ReferralCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\ReferralCommission>
 */
class ReferralCommissionFactory extends Factory
{
    protected $model = ReferralCommission::class;

    public function definition(): array
    {
        $commissionRate = fake()->randomElement([5, 10, 15, 20]);
        $commissionAmount = round(fake()->randomFloat(2, 10, 200) * ($commissionRate / 100), 2);

        return [
            'referrer_tenant_id' => Tenant::factory(),
            'referred_tenant_id' => Tenant::factory(),
            'payment_id' => Payment::factory(),
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'currency' => 'USD',
            'status' => fake()->randomElement(['pending', 'approved', 'paid', 'rejected']),
            'paid_at' => null,
            'payout_method' => null,
            'payout_reference' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'paid_at' => null,
            'notes' => 'Comisión rechazada: ' . fake()->sentence(),
        ]);
    }
}
