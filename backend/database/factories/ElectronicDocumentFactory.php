<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\Company;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Customer;
use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SRI\ElectronicDocument>
 */
class ElectronicDocumentFactory extends Factory
{
    protected $model = ElectronicDocument::class;

    public function definition(): array
    {
        $subtotal12 = fake()->randomFloat(2, 10, 1000);
        $tax = round($subtotal12 * 0.12, 2);
        $total = $subtotal12 + $tax;

        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'emission_point_id' => EmissionPoint::factory(),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'document_type' => DocumentType::FACTURA,
            'environment' => fake()->randomElement(['1', '2']),
            'series' => fake()->numerify('###-###'),
            'sequential' => fake()->unique()->numerify('#########'),
            'access_key' => fake()->numerify(str_repeat('#', 49)),
            'status' => DocumentStatus::AUTHORIZED,
            'issue_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'authorization_date' => now(),
            'authorization_number' => fake()->numerify(str_repeat('#', 49)),
            'subtotal_0' => 0,
            'subtotal_5' => 0,
            'subtotal_12' => $subtotal12,
            'subtotal_15' => 0,
            'subtotal_no_tax' => 0,
            'total_discount' => 0,
            'total_tax' => $tax,
            'tip' => 0,
            'total' => $total,
            'currency' => 'DOLAR',
            'payment_methods' => [
                ['code' => '01', 'amount' => $total, 'term' => 0, 'time_unit' => 'dias'],
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::FACTURA,
        ]);
    }

    public function creditNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::NOTA_CREDITO,
        ]);
    }

    public function debitNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::NOTA_DEBITO,
        ]);
    }

    public function retention(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::RETENCION,
        ]);
    }

    public function waybill(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::GUIA_REMISION,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::DRAFT,
            'access_key' => null,
            'authorization_date' => null,
            'authorization_number' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::PROCESSING,
            'authorization_date' => null,
            'authorization_number' => null,
        ]);
    }

    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::AUTHORIZED,
            'authorization_date' => now(),
            'authorization_number' => fake()->numerify(str_repeat('#', 49)),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::REJECTED,
            'authorization_date' => null,
            'authorization_number' => null,
            'sri_messages' => [
                ['identifier' => '35', 'message' => 'Error en el comprobante', 'type' => 'ERROR'],
            ],
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::VOIDED,
            'canceled_at' => now(),
            'cancel_reason' => fake()->sentence(),
        ]);
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'environment' => '2',
        ]);
    }

    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'environment' => '1',
        ]);
    }
}
