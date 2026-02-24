<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Product;
use App\Models\Tenant\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 1, 500);
        $taxCode = fake()->randomElement(['0', '2', '4']); // 0%, 12%, 15%
        $taxRate = match ($taxCode) {
            '0' => 0,
            '2' => 12,
            '4' => 15,
            default => 15,
        };

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => null,
            'type' => fake()->randomElement(['product', 'service']),
            'main_code' => fake()->unique()->bothify('PROD-####'),
            'aux_code' => fake()->optional()->bothify('AUX-####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit_price' => $unitPrice,
            'cost_price' => fake()->optional()->randomFloat(2, 0.5, $unitPrice * 0.8),
            'tax_percentage_code' => $taxCode,
            'tax_rate' => $taxRate,
            'has_ice' => false,
            'ice_code' => null,
            'track_inventory' => fake()->boolean(70),
            'current_stock' => fake()->randomFloat(2, 0, 1000),
            'min_stock' => fake()->randomFloat(2, 0, 50),
            'unit_of_measure' => fake()->randomElement(['unidad', 'kg', 'l', 'm', 'servicio']),
            'is_active' => true,
            'is_favorite' => fake()->boolean(20),
            'barcode' => fake()->optional()->ean13(),
        ];
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
            'track_inventory' => true,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
            'track_inventory' => false,
            'current_stock' => 0,
            'min_stock' => 0,
            'unit_of_measure' => 'servicio',
        ]);
    }

    public function withCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $category->tenant_id,
            'category_id' => $category->id,
        ]);
    }

    public function taxFree(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_percentage_code' => '0',
            'tax_rate' => 0,
        ]);
    }

    public function with12Iva(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_percentage_code' => '2',
            'tax_rate' => 12,
        ]);
    }

    public function with15Iva(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_percentage_code' => '4',
            'tax_rate' => 15,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => true,
            'current_stock' => 5,
            'min_stock' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => true,
            'current_stock' => 0,
            'min_stock' => 10,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }
}
