<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Product;
use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\DocumentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SRI\DocumentItem>
 */
class DocumentItemFactory extends Factory
{
    protected $model = DocumentItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 1, 500);
        $discount = fake()->randomFloat(2, 0, $quantity * $unitPrice * 0.1);
        $subtotal = ($quantity * $unitPrice) - $discount;
        $taxRate = fake()->randomElement([0, 12, 15]);
        $taxValue = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxValue;

        return [
            'tenant_id' => Tenant::factory(),
            'electronic_document_id' => ElectronicDocument::factory(),
            'product_id' => Product::factory(),
            'main_code' => fake()->bothify('PROD-####'),
            'aux_code' => fake()->optional()->bothify('AUX-####'),
            'description' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'discount_percentage' => $discount > 0 ? round(($discount / ($quantity * $unitPrice)) * 100, 2) : 0,
            'subtotal' => $subtotal,
            'tax_code' => '2', // IVA
            'tax_percentage_code' => match ($taxRate) {
                0 => '0',
                12 => '2',
                15 => '4',
                default => '2',
            },
            'tax_rate' => $taxRate,
            'tax_value' => $taxValue,
            'total' => $total,
        ];
    }

    public function withProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'main_code' => $product->main_code,
            'aux_code' => $product->aux_code,
            'description' => $product->name,
            'unit_price' => $product->unit_price,
            'tax_percentage_code' => $product->tax_percentage_code,
            'tax_rate' => $product->tax_rate,
        ]);
    }

    public function taxFree(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            return [
                'tax_percentage_code' => '0',
                'tax_rate' => 0,
                'tax_value' => 0,
                'total' => $subtotal,
            ];
        });
    }

    public function with12Iva(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $taxValue = round($subtotal * 0.12, 2);
            return [
                'tax_percentage_code' => '2',
                'tax_rate' => 12,
                'tax_value' => $taxValue,
                'total' => $subtotal + $taxValue,
            ];
        });
    }

    public function with15Iva(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $taxValue = round($subtotal * 0.15, 2);
            return [
                'tax_percentage_code' => '4',
                'tax_rate' => 15,
                'tax_value' => $taxValue,
                'total' => $subtotal + $taxValue,
            ];
        });
    }
}
