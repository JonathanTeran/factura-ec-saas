<?php

namespace Database\Factories;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['04', '05', '06']);
        // Cédula/RUC con dígito verificador VÁLIDO: la emisión pre-valida la
        // identificación (SriPreValidator) antes de enviar al SRI.
        $identification = match ($type) {
            '04' => self::validCedula() . '001',            // RUC persona natural
            '05' => self::validCedula(),                    // Cédula
            '06' => fake()->bothify('??######'),            // Pasaporte
        };

        return [
            'tenant_id' => Tenant::factory(),
            'identification_type' => $type,
            'identification' => $identification,
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_active' => true,
            'total_invoiced' => 0,
        ];
    }

    public function consumidorFinal(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '07',
            'identification' => '9999999999999',
            'name' => 'CONSUMIDOR FINAL',
        ]);
    }

    public function withRuc(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '04',
            'identification' => self::validCedula() . '001',
        ]);
    }

    public function withCedula(): static
    {
        return $this->state(fn (array $attributes) => [
            'identification_type' => '05',
            'identification' => self::validCedula(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Cédula ecuatoriana válida: provincia 01-24, tercer dígito 0-5 (persona
     * natural, para que también sirva de núcleo de RUC) y verificador módulo 10.
     */
    private static function validCedula(): string
    {
        $provincia = str_pad((string) fake()->numberBetween(1, 24), 2, '0', STR_PAD_LEFT);
        $cuerpo = $provincia
            .fake()->numberBetween(0, 5)
            .fake()->numerify('######');

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = ((int) $cuerpo[$i]) * $coeficientes[$i];
            $suma += $valor >= 10 ? $valor - 9 : $valor;
        }
        $residuo = $suma % 10;
        $verificador = $residuo === 0 ? 0 : 10 - $residuo;

        return $cuerpo.$verificador;
    }
}
