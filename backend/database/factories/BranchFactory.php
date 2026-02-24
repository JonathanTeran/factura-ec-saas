<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('###'),
            'name' => fake()->randomElement(['Matriz', 'Sucursal Norte', 'Sucursal Sur', 'Agencia Centro']) . ' ' . fake()->city(),
            'address' => fake()->address(),
            'is_main' => false,
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '001',
            'name' => 'Matriz',
            'is_main' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
