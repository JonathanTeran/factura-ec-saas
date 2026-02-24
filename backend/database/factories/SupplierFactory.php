<?php

namespace Database\Factories;

use App\Models\Tenant\Supplier;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'identification_type' => '04',
            'identification' => fake()->unique()->numerify('17########001'),
            'business_name' => fake()->company(),
            'commercial_name' => fake()->optional()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_active' => true,
            'is_withholding_agent' => fake()->boolean(30),
            'total_purchased' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withholdingAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_withholding_agent' => true,
        ]);
    }
}
