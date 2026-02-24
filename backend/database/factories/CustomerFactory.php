<?php

namespace Database\Factories;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['04', '05', '06']);
        $identification = match ($type) {
            '04' => fake()->numerify('#########') . '001', // RUC
            '05' => fake()->numerify('##########'),         // Cedula
            '06' => fake()->bothify('??######'),            // Pasaporte
        };

        return [
            'tenant_id' => Tenant::factory(),
            'identification_type' => $type,
            'identification' => $identification,
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_active' => true,
            'total_invoiced' => 0,
        ];
    }

    public function consumidorFinal(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '07',
            'identification' => '9999999999999',
            'name' => 'CONSUMIDOR FINAL',
        ]);
    }

    public function withRuc(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '04',
            'identification' => fake()->numerify('#########') . '001',
        ]);
    }

    public function withCedula(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '05',
            'identification' => fake()->numerify('##########'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
