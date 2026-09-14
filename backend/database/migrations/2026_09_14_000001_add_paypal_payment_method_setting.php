<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PayPal como método de pago de las suscripciones. Nace desactivado: el super
 * admin lo activa en Sistema → PayPal después de cargar las credenciales.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('payment_method_settings')->where('code', 'paypal')->exists()) {
            return;
        }

        DB::table('payment_method_settings')->insert([
            'code' => 'paypal',
            'name' => 'PayPal',
            'description' => 'Pago con cuenta PayPal o con tarjeta a través de PayPal. La suscripción se activa al confirmarse el cobro.',
            'is_enabled' => false,
            'requires_gateway' => true,
            'instructions' => 'Configura las credenciales en Sistema → PayPal antes de activarlo.',
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('payment_method_settings')->where('code', 'paypal')->delete();
    }
};
