<?php

namespace Database\Factories;

use App\Models\Tenant\Tenant;
use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\WithholdingDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SRI\WithholdingDetail>
 */
class WithholdingDetailFactory extends Factory
{
    protected $model = WithholdingDetail::class;

    public function definition(): array
    {
        $taxBase = fake()->randomFloat(2, 100, 10000);
        $percentage = fake()->randomElement([1, 2, 8, 10, 30, 70, 100]);
        $taxValue = round($taxBase * ($percentage / 100), 2);

        return [
            'tenant_id' => Tenant::factory(),
            'electronic_document_id' => ElectronicDocument::factory(),
            'tax_code' => fake()->randomElement(['1', '2']), // 1 = Renta, 2 = IVA
            'retention_code' => fake()->numerify('###'),
            'tax_base' => $taxBase,
            'percentage' => $percentage,
            'tax_value' => $taxValue,
            'source_document_type' => '01', // Factura
            'source_document_number' => fake()->numerify('###-###-#########'),
            'source_document_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function rentaRetention(): static
    {
        $codes = [
            ['code' => '303', 'percentage' => 10, 'name' => 'Honorarios profesionales'],
            ['code' => '304', 'percentage' => 8, 'name' => 'Servicios predomina mano de obra'],
            ['code' => '307', 'percentage' => 2, 'name' => 'Servicios entre sociedades'],
            ['code' => '310', 'percentage' => 1, 'name' => 'Transporte privado'],
            ['code' => '312', 'percentage' => 1, 'name' => 'Transferencia de bienes muebles'],
        ];
        $selected = fake()->randomElement($codes);

        return $this->state(function (array $attributes) use ($selected) {
            $taxBase = $attributes['tax_base'];
            return [
                'tax_code' => '1',
                'retention_code' => $selected['code'],
                'percentage' => $selected['percentage'],
                'tax_value' => round($taxBase * ($selected['percentage'] / 100), 2),
            ];
        });
    }

    public function ivaRetention(): static
    {
        $codes = [
            ['code' => '721', 'percentage' => 30, 'name' => 'Retención 30% IVA'],
            ['code' => '723', 'percentage' => 70, 'name' => 'Retención 70% IVA'],
            ['code' => '725', 'percentage' => 100, 'name' => 'Retención 100% IVA'],
        ];
        $selected = fake()->randomElement($codes);

        return $this->state(function (array $attributes) use ($selected) {
            $taxBase = $attributes['tax_base'];
            return [
                'tax_code' => '2',
                'retention_code' => $selected['code'],
                'percentage' => $selected['percentage'],
                'tax_value' => round($taxBase * ($selected['percentage'] / 100), 2),
            ];
        });
    }

    public function fromInvoice(string $invoiceNumber, \DateTime $invoiceDate): static
    {
        return $this->state(fn (array $attributes) => [
            'source_document_type' => '01',
            'source_document_number' => $invoiceNumber,
            'source_document_date' => $invoiceDate,
        ]);
    }
}
