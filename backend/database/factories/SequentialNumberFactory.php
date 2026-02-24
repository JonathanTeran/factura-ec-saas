<?php

namespace Database\Factories;

use App\Models\Tenant\EmissionPoint;
use App\Models\SRI\SequentialNumber;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SRI\SequentialNumber>
 */
class SequentialNumberFactory extends Factory
{
    protected $model = SequentialNumber::class;

    public function definition(): array
    {
        return [
            'emission_point_id' => EmissionPoint::factory(),
            'document_type' => fake()->randomElement(DocumentType::cases()),
            'current_number' => fake()->numberBetween(1, 999999999),
        ];
    }

    public function forInvoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::FACTURA,
        ]);
    }

    public function forCreditNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::NOTA_CREDITO,
        ]);
    }

    public function forDebitNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::NOTA_DEBITO,
        ]);
    }

    public function forRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::RETENCION,
        ]);
    }

    public function forWaybill(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::GUIA_REMISION,
        ]);
    }

    public function startingAt(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'current_number' => $number,
        ]);
    }
}
