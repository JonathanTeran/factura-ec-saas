<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        $subtotal15 = fake()->randomFloat(2, 50, 5000);
        $tax = round($subtotal15 * 0.15, 2);

        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'document_type' => '01',
            'supplier_document_number' => fake()->numerify('###-###-#########'),
            'supplier_authorization' => fake()->numerify('#########################################'),
            'issue_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'authorization_date' => fake()->optional()->dateTimeBetween('-3 months', 'now'),
            'subtotal_0' => 0,
            'subtotal_5' => 0,
            'subtotal_12' => 0,
            'subtotal_15' => $subtotal15,
            'subtotal_no_tax' => 0,
            'total_discount' => 0,
            'total_tax' => $tax,
            'total' => $subtotal15 + $tax,
            'status' => PurchaseStatus::REGISTERED,
            'payment_methods' => [['method' => '20', 'amount' => $subtotal15 + $tax]],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseStatus::PAID,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseStatus::VOIDED,
        ]);
    }
}
