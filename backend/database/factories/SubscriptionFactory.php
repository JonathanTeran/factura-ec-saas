<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 year', 'now');
        $cycle = fake()->randomElement(['monthly', 'yearly']);
        $endsAt = $cycle === 'monthly'
            ? (clone $startsAt)->modify('+1 month')
            : (clone $startsAt)->modify('+1 year');

        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => $cycle,
            'amount' => fake()->randomFloat(2, 9.99, 199.99),
            'discount_percent' => 0,
            'currency' => 'USD',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'trial_ends_at' => null,
            'canceled_at' => null,
            'cancellation_reason' => null,
            'auto_renew' => true,
            'payment_method' => fake()->randomElement(['credit_card', 'debit_card', 'transfer']),
            'next_payment_at' => $endsAt,
            'last_payment_at' => $startsAt,
            'failed_payments_count' => 0,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'next_payment_at' => now()->addMonth(),
        ]);
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::TRIALING,
            'trial_ends_at' => now()->addDays(14),
            'amount' => 0,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED,
            'canceled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
            'auto_renew' => false,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PAST_DUE,
            'ends_at' => now()->subDays(5),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
        ]);
    }
}
