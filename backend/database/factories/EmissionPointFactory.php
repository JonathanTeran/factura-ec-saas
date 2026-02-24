<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\EmissionPoint>
 */
class EmissionPointFactory extends Factory
{
    protected $model = EmissionPoint::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => Branch::factory(),
            'code' => fake()->unique()->numerify('###'),
            'name' => fake()->optional()->randomElement(['Caja 1', 'Caja 2', 'Punto de venta', 'Bodega']),
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '001',
            'name' => 'Punto de emisión principal',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
