<?php

namespace Database\Factories;

use App\Enums\PosSessionStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\PosSession;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\PosSession>
 */
class PosSessionFactory extends Factory
{
    protected $model = PosSession::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'emission_point_id' => EmissionPoint::factory(),
            'opened_by' => User::factory(),
            'opening_amount' => fake()->randomFloat(2, 0, 500),
            'total_transactions' => 0,
            'total_cash' => 0,
            'total_card' => 0,
            'total_transfer' => 0,
            'total_other' => 0,
            'total_sales' => 0,
            'status' => PosSessionStatus::OPEN,
            'opened_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $totalSales = fake()->randomFloat(2, 100, 5000);
            $opening = $attributes['opening_amount'] ?? 0;
            $totalCash = $totalSales * 0.6;

            return [
                'status' => PosSessionStatus::CLOSED,
                'closed_by' => $attributes['opened_by'] ?? User::factory(),
                'closing_amount' => $opening + $totalCash,
                'expected_amount' => $opening + $totalCash,
                'difference' => 0,
                'total_transactions' => fake()->numberBetween(5, 50),
                'total_cash' => $totalCash,
                'total_card' => $totalSales * 0.3,
                'total_transfer' => $totalSales * 0.1,
                'total_sales' => $totalSales,
                'closed_at' => now(),
            ];
        });
    }
}
