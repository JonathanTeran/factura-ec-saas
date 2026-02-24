<?php

namespace Database\Seeders;

use App\Models\Billing\PaymentMethodSetting;
use Illuminate\Database\Seeder;

class PaymentMethodSettingSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'transfer',
                'name' => 'Transferencia Bancaria',
                'description' => 'Pago mediante transferencia o depósito bancario',
                'is_enabled' => true,
                'requires_gateway' => false,
                'instructions' => null,
                'sort_order' => 1,
            ],
            [
                'code' => 'credit_card',
                'name' => 'Tarjeta de Crédito',
                'description' => 'Pago con tarjeta de crédito',
                'is_enabled' => false,
                'requires_gateway' => true,
                'instructions' => 'Próximamente disponible',
                'sort_order' => 2,
            ],
            [
                'code' => 'debit_card',
                'name' => 'Tarjeta de Débito',
                'description' => 'Pago con tarjeta de débito',
                'is_enabled' => false,
                'requires_gateway' => true,
                'instructions' => 'Próximamente disponible',
                'sort_order' => 3,
            ],
            [
                'code' => 'payphone',
                'name' => 'PayPhone',
                'description' => 'Pago a través de PayPhone',
                'is_enabled' => false,
                'requires_gateway' => true,
                'instructions' => 'Próximamente disponible',
                'sort_order' => 4,
            ],
            [
                'code' => 'kushki',
                'name' => 'Kushki',
                'description' => 'Pago a través de Kushki',
                'is_enabled' => false,
                'requires_gateway' => true,
                'instructions' => 'Próximamente disponible',
                'sort_order' => 5,
            ],
            [
                'code' => 'stripe',
                'name' => 'Stripe',
                'description' => 'Pago a través de Stripe',
                'is_enabled' => false,
                'requires_gateway' => true,
                'instructions' => 'Próximamente disponible',
                'sort_order' => 6,
            ],
            [
                'code' => 'cash',
                'name' => 'Efectivo',
                'description' => 'Pago en efectivo',
                'is_enabled' => false,
                'requires_gateway' => false,
                'instructions' => null,
                'sort_order' => 7,
            ],
            [
                'code' => 'other',
                'name' => 'Otro',
                'description' => 'Otro método de pago',
                'is_enabled' => false,
                'requires_gateway' => false,
                'instructions' => null,
                'sort_order' => 8,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethodSetting::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
