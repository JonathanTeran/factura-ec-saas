<?php

namespace Database\Factories;

use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plain = ApiKey::generatePlainKey();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true),
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixFrom($plain),
            'permissions' => ['*'],
            'rate_limit_per_minute' => 60,
            'is_active' => true,
        ];
    }

    /** Fija la llave en claro para poder usarla en las pruebas. */
    public function withPlainKey(string $plain): static
    {
        return $this->state([
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixFrom($plain),
        ]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
