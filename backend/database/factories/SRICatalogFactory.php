<?php

namespace Database\Factories;

use App\Models\SRI\SRICatalog;
use App\Enums\SRICatalogType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SRI\SRICatalog>
 */
class SRICatalogFactory extends Factory
{
    protected $model = SRICatalog::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(SRICatalogType::cases()),
            'code' => fake()->unique()->numerify('##'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'parent_code' => null,
            'metadata' => [],
            'is_active' => true,
        ];
    }

    public function documentType(): static
    {
        $types = [
            ['code' => '01', 'name' => 'Factura'],
            ['code' => '04', 'name' => 'Nota de Crédito'],
            ['code' => '05', 'name' => 'Nota de Débito'],
            ['code' => '06', 'name' => 'Guía de Remisión'],
            ['code' => '07', 'name' => 'Comprobante de Retención'],
        ];
        $selected = fake()->randomElement($types);

        return $this->state(fn (array $attributes) => [
            'type' => SRICatalogType::DOCUMENT_TYPE,
            'code' => $selected['code'],
            'name' => $selected['name'],
        ]);
    }

    public function taxType(): static
    {
        $types = [
            ['code' => '2', 'name' => 'IVA'],
            ['code' => '3', 'name' => 'ICE'],
            ['code' => '5', 'name' => 'IRBPNR'],
        ];
        $selected = fake()->randomElement($types);

        return $this->state(fn (array $attributes) => [
            'type' => SRICatalogType::TAX_TYPE,
            'code' => $selected['code'],
            'name' => $selected['name'],
        ]);
    }

    public function taxPercentage(): static
    {
        $percentages = [
            ['code' => '0', 'name' => '0%', 'rate' => 0],
            ['code' => '2', 'name' => '12%', 'rate' => 12],
            ['code' => '4', 'name' => '15%', 'rate' => 15],
            ['code' => '6', 'name' => 'No Objeto de Impuesto', 'rate' => 0],
            ['code' => '7', 'name' => 'Exento de IVA', 'rate' => 0],
        ];
        $selected = fake()->randomElement($percentages);

        return $this->state(fn (array $attributes) => [
            'type' => SRICatalogType::TAX_PERCENTAGE,
            'code' => $selected['code'],
            'name' => $selected['name'],
            'metadata' => ['rate' => $selected['rate']],
        ]);
    }

    public function paymentMethod(): static
    {
        $methods = [
            ['code' => '01', 'name' => 'Sin utilización del sistema financiero'],
            ['code' => '15', 'name' => 'Compensación de deudas'],
            ['code' => '16', 'name' => 'Tarjeta de débito'],
            ['code' => '17', 'name' => 'Dinero electrónico'],
            ['code' => '18', 'name' => 'Tarjeta prepago'],
            ['code' => '19', 'name' => 'Tarjeta de crédito'],
            ['code' => '20', 'name' => 'Otros con utilización del sistema financiero'],
        ];
        $selected = fake()->randomElement($methods);

        return $this->state(fn (array $attributes) => [
            'type' => SRICatalogType::PAYMENT_METHOD,
            'code' => $selected['code'],
            'name' => $selected['name'],
        ]);
    }

    public function identificationType(): static
    {
        $types = [
            ['code' => '04', 'name' => 'RUC'],
            ['code' => '05', 'name' => 'Cédula'],
            ['code' => '06', 'name' => 'Pasaporte'],
            ['code' => '07', 'name' => 'Consumidor Final'],
            ['code' => '08', 'name' => 'Identificación del Exterior'],
        ];
        $selected = fake()->randomElement($types);

        return $this->state(fn (array $attributes) => [
            'type' => SRICatalogType::IDENTIFICATION_TYPE,
            'code' => $selected['code'],
            'name' => $selected['name'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
