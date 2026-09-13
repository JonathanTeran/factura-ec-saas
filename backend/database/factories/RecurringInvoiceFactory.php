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
        $start = now()->toDateString();

        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'emission_point_id' => EmissionPoint::factory(),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'name' => fake()->words(3, true),
            'frequency' => fake()->randomElement(RecurringInvoice::FREQUENCIES),
            'start_date' => $start,
            'next_issue_date' => $start,
            'end_date' => null,
            'status' => 'active',
            'items' => [
                [
                    'product_id' => null,
                    'main_code' => 'SERV-001',
                    'description' => fake()->words(3, true),
                    'quantity' => 1,
                    'unit_price' => fake()->randomFloat(2, 10, 500),
                    'discount' => 0,
                    'tax_rate' => 15,
                    'tax_percentage_code' => '4',
                ],
            ],
            'payment_methods' => [['code' => '20', 'term' => 0, 'time_unit' => 'dias']],
            'currency' => 'DOLAR',
            'total_issued' => 0,
            'max_issues' => null,
            'notify_before_issue' => true,
            'notify_days_before' => 1,
            'auto_send' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'paused']);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => ['frequency' => 'monthly']);
    }
}
