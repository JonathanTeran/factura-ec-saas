<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Quote;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = round($subtotal * 0.15, 2);

        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'quote_number' => 'COT-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => QuoteStatus::DRAFT,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'subtotal' => $subtotal,
            'total_discount' => 0,
            'total_tax' => $tax,
            'total' => $subtotal + $tax,
            'notes' => null,
            'payment_terms' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => QuoteStatus::SENT, 'sent_at' => now()]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => QuoteStatus::ACCEPTED, 'accepted_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => QuoteStatus::REJECTED, 'rejected_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => QuoteStatus::EXPIRED, 'expiry_date' => now()->subDays(3)->toDateString()]);
    }
}
