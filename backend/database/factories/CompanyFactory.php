<?php

namespace Database\Factories;

use App\Models\Tenant\Company;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $ruc = fake()->numerify('#########') . '001';

        return [
            'tenant_id' => Tenant::factory(),
            'ruc' => $ruc,
            'business_name' => fake()->company(),
            'trade_name' => fake()->optional()->company(),
            'legal_representative' => fake()->name(),
            'taxpayer_type' => fake()->randomElement(['natural', 'juridical']),
            'obligated_accounting' => fake()->boolean(),
            'special_taxpayer' => false,
            'special_taxpayer_number' => null,
            'retention_agent_number' => null,
            'rimpe_type' => 'none',
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'logo_path' => null,
            'sri_environment' => '1',
            'is_active' => true,
        ];
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'sri_environment' => '2',
        ]);
    }

    public function withSignature(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_path' => 'signatures/test.p12',
            'signature_password' => encrypt('test123'),
            'signature_expires_at' => now()->addYear(),
            'signature_issuer' => 'Security Data',
            'signature_subject' => $attributes['business_name'] ?? 'Test Company',
        ]);
    }
}
