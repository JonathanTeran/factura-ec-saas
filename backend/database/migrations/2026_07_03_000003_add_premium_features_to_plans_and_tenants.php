<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nuevas funcionalidades premium, disponibles del plan Profesional en adelante.
 *
 * Avanzadas (Profesional + Enterprise):
 *   - has_priority_queue    Cola de emisión prioritaria al SRI (aplicada de verdad).
 *   - has_bulk_operations   Emisión y carga masiva por lotes.
 *   - has_custom_roles      Roles y permisos personalizados.
 *
 * Exclusivas de Enterprise (nivel de servicio):
 *   - has_sso                 Inicio de sesión corporativo (SSO/SAML).
 *   - has_dedicated_manager   Ejecutivo de cuenta dedicado.
 *   - has_custom_integrations Integraciones a medida.
 *   - has_sla                 SLA de disponibilidad garantizado.
 */
return new class extends Migration
{
    private array $features = [
        'has_priority_queue',
        'has_bulk_operations',
        'has_custom_roles',
        'has_sso',
        'has_dedicated_manager',
        'has_custom_integrations',
        'has_sla',
    ];

    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $after = 'has_ai_categorization';
            foreach ($this->features as $feature) {
                $table->boolean($feature)->default(false)->after($after);
                $after = $feature;
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            $after = 'has_thermal_printer';
            foreach ($this->features as $feature) {
                $table->boolean($feature)->default(false)->after($after);
                $after = $feature;
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn($this->features);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn($this->features);
        });
    }
};
