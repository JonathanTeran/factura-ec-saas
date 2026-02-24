<?php

namespace Database\Factories;

use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'owner_email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
            'current_plan_id' => Plan::factory(),
            'subscription_status' => 'active',
            'max_documents_per_month' => 100,
            'max_users' => 5,
            'max_companies' => 2,
            'max_emission_points' => 5,
            'has_api_access' => true,
            'has_inventory' => false,
            'has_pos' => false,
            'has_recurring_invoices' => false,
            'has_advanced_reports' => false,
            'has_whitelabel_ride' => false,
            'documents_this_month' => 0,
            'settings' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'subscription_status' => 'cancelled',
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'subscription_status' => 'past_due',
            'trial_ends_at' => now()->subDays(5),
        ]);
    }

    public function withPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_pos' => true,
        ]);
    }

    public function withAiCategorization(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_ai_categorization' => true,
        ]);
    }
}
