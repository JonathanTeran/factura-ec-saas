<?php

namespace Database\Factories;

use App\Models\Billing\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Basico', 'Profesional', 'Empresarial', 'Premium']);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'price_monthly' => fake()->randomFloat(2, 9.99, 199.99),
            'price_yearly' => fake()->randomFloat(2, 99.99, 1999.99),
            'currency' => 'USD',
            'max_documents_per_month' => fake()->randomElement([100, 500, 1000, -1]),
            'max_users' => fake()->randomElement([1, 3, 10, -1]),
            'max_companies' => fake()->randomElement([1, 2, 5, -1]),
            'max_emission_points' => fake()->randomElement([1, 5, 10, -1]),
            'has_electronic_signature' => fake()->boolean(50),
            'has_api_access' => fake()->boolean(50),
            'has_inventory' => fake()->boolean(50),
            'has_pos' => fake()->boolean(30),
            'has_recurring_invoices' => fake()->boolean(40),
            'has_proformas' => fake()->boolean(40),
            'has_ats' => fake()->boolean(50),
            'has_thermal_printer' => fake()->boolean(30),
            'has_advanced_reports' => fake()->boolean(30),
            'has_whitelabel_ride' => fake()->boolean(20),
            'has_webhooks' => fake()->boolean(20),
            'has_client_portal' => fake()->boolean(30),
            'has_multi_currency' => fake()->boolean(20),
            'has_accountant_access' => fake()->boolean(30),
            'has_ai_categorization' => fake()->boolean(20),
            'support_level' => fake()->randomElement(['community', 'email', 'priority']),
            'support_response_hours' => fake()->randomElement([72, 48, 24, 4]),
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(1, 10),
            'trial_days' => 14,
            'features_json' => ['feature1', 'feature2'],
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Gratis',
            'slug' => 'gratis',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_documents_per_month' => 10,
            'max_users' => 1,
            'max_companies' => 1,
            'max_emission_points' => 1,
            'has_api_access' => false,
            'has_pos' => false,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Ilimitado',
            'slug' => 'ilimitado',
            'max_documents_per_month' => -1,
            'max_users' => -1,
            'max_companies' => -1,
            'max_emission_points' => -1,
            'has_electronic_signature' => true,
            'has_api_access' => true,
            'has_inventory' => true,
            'has_pos' => true,
            'has_recurring_invoices' => true,
            'has_ats' => true,
            'has_advanced_reports' => true,
            'has_webhooks' => true,
            'has_ai_categorization' => true,
        ]);
    }

    public function withPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_pos' => true,
        ]);
    }
}
