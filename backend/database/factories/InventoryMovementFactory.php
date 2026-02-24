<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Product;
use App\Models\Tenant\InventoryMovement;
use App\Models\User;
use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $type = fake()->randomElement(MovementType::cases());
        $quantity = fake()->randomFloat(2, 1, 100);
        $previousStock = fake()->randomFloat(2, 0, 500);
        $newStock = $type->isIncoming()
            ? $previousStock + $quantity
            : max(0, $previousStock - $quantity);

        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'reference_type' => null,
            'reference_id' => null,
            'movement_type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'unit_cost' => fake()->optional()->randomFloat(2, 1, 100),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function initial(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->randomFloat(2, 10, 500);
            return [
                'movement_type' => MovementType::INITIAL,
                'previous_stock' => 0,
                'new_stock' => $quantity,
                'quantity' => $quantity,
                'notes' => 'Inventario inicial',
            ];
        });
    }

    public function purchase(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $previousStock = $attributes['previous_stock'];
            return [
                'movement_type' => MovementType::PURCHASE,
                'new_stock' => $previousStock + $quantity,
                'notes' => 'Compra de mercadería',
            ];
        });
    }

    public function sale(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $previousStock = $attributes['previous_stock'];
            return [
                'movement_type' => MovementType::SALE,
                'new_stock' => max(0, $previousStock - $quantity),
                'notes' => 'Venta de producto',
            ];
        });
    }

    public function adjustmentIn(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $previousStock = $attributes['previous_stock'];
            return [
                'movement_type' => MovementType::ADJUSTMENT_IN,
                'new_stock' => $previousStock + $quantity,
                'notes' => 'Ajuste de inventario positivo',
            ];
        });
    }

    public function adjustmentOut(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $previousStock = $attributes['previous_stock'];
            return [
                'movement_type' => MovementType::ADJUSTMENT_OUT,
                'new_stock' => max(0, $previousStock - $quantity),
                'notes' => 'Ajuste de inventario negativo',
            ];
        });
    }

    public function damage(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $previousStock = $attributes['previous_stock'];
            return [
                'movement_type' => MovementType::DAMAGE,
                'new_stock' => max(0, $previousStock - $quantity),
                'notes' => 'Producto dañado',
            ];
        });
    }

    public function withProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'previous_stock' => $product->current_stock,
        ]);
    }
}
