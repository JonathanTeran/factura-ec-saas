<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 9.99, 199.99);
        $taxAmount = round($amount * 0.15, 2);

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
            'gateway' => fake()->randomElement(['stripe', 'paypal', 'paymentez']),
            'gateway_payment_id' => fake()->uuid(),
            'gateway_transaction_id' => fake()->uuid(),
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount,
            'currency' => 'USD',
            'status' => PaymentStatus::COMPLETED,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'description' => 'Pago de suscripción',
            'billing_name' => fake()->name(),
            'billing_email' => fake()->email(),
            'billing_identification' => fake()->numerify('##########'),
            'billing_address' => fake()->address(),
            'billing_phone' => fake()->phoneNumber(),
            'gateway_response' => [],
            'paid_at' => now(),
            'refunded_at' => null,
            'refund_amount' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'paid_at' => null,
            'gateway_response' => ['error' => 'Payment declined'],
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
            'refund_amount' => $attributes['amount'],
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'payment_method' => PaymentMethod::CREDIT_CARD,
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'paypal',
            'payment_method' => PaymentMethod::OTHER,
        ]);
    }
}
