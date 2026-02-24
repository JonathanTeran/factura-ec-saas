<?php

namespace Database\Factories;

use App\Models\Billing\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['percentage', 'fixed']);

        return [
            'code' => strtoupper(Str::random(8)),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'discount_type' => $type,
            'discount_value' => $type === 'percentage'
                ? fake()->numberBetween(5, 50)
                : fake()->randomFloat(2, 5, 50),
            'max_discount_amount' => $type === 'percentage'
                ? fake()->optional()->randomFloat(2, 10, 100)
                : null,
            'min_purchase_amount' => fake()->optional()->randomFloat(2, 10, 50),
            'applicable_plans' => null,
            'max_uses' => fake()->optional()->numberBetween(10, 1000),
            'max_uses_per_tenant' => fake()->randomElement([1, 2, null]),
            'current_uses' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(fake()->numberBetween(1, 12)),
            'is_active' => true,
        ];
    }

    public function percentage(int $percent = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $percent,
        ]);
    }

    public function fixed(float $amount = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'fixed',
            'discount_value' => $amount,
            'max_discount_amount' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => null,
            'max_uses_per_tenant' => null,
            'expires_at' => null,
        ]);
    }

    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 1,
            'max_uses_per_tenant' => 1,
        ]);
    }
}
