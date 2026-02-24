<?php

namespace Database\Factories;

use App\Models\Tenant\PosSession;
use App\Models\Tenant\PosTransaction;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\PosTransaction>
 */
class PosTransactionFactory extends Factory
{
    protected $model = PosTransaction::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 500);
        $tax = round($subtotal * 0.15, 2);
        $total = $subtotal + $tax;

        return [
            'tenant_id' => Tenant::factory(),
            'pos_session_id' => PosSession::factory(),
            'transaction_number' => 'POS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'payment_method' => fake()->randomElement(['cash', 'card', 'transfer']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => 0,
            'total' => $total,
            'amount_received' => $total,
            'change_amount' => 0,
            'status' => 'completed',
        ];
    }

    public function cash(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'] ?? 50;
            $received = ceil($total / 10) * 10;
            return [
                'payment_method' => 'cash',
                'amount_received' => $received,
                'change_amount' => $received - $total,
            ];
        });
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'voided',
        ]);
    }
}
