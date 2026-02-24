<?php

namespace Database\Factories;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\RecurringInvoice;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\RecurringInvoice>
 */
class RecurringInvoiceFactory extends Factory
{
    protected $model = RecurringInvoice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'emission_point_id' => EmissionPoint::factory(),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'name' => fake()->sentence(3),
            'frequency' => fake()->randomElement(['weekly', 'biweekly', 'monthly', 'quarterly', 'semiannual', 'annual']),
            'next_issue_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->optional()->dateTimeBetween('+6 months', '+2 years'),
            'max_issues' => fake()->optional()->numberBetween(6, 24),
            'issues_count' => 0,
            'items_json' => [
                [
                    'description' => fake()->words(3, true),
                    'quantity' => 1,
                    'unit_price' => fake()->randomFloat(2, 10, 500),
                    'tax_percentage_code' => '4',
                    'tax_rate' => 15,
                ],
            ],
            'payment_methods_json' => [['method' => '20', 'term' => 30]],
            'is_active' => true,
            'notify_before_days' => 3,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }
}
