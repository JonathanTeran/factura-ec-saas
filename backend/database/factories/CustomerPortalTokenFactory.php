<?php

namespace Database\Factories;

use App\Models\Portal\CustomerPortalToken;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerPortalTokenFactory extends Factory
{
    protected $model = CustomerPortalToken::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => fake()->safeEmail(),
            'identification' => fake()->numerify('##########'),
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24),
            'used_at' => null,
            'ip_address' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);
    }
}
