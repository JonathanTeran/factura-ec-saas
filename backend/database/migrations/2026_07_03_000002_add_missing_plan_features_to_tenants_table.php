<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas de features de plan que existían en `plans` pero no se
 * denormalizaban al tenant, por lo que no se podían hacer cumplir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('has_client_portal')->default(false)->after('has_ai_categorization');
            $table->boolean('has_multi_currency')->default(false)->after('has_client_portal');
            $table->boolean('has_thermal_printer')->default(false)->after('has_multi_currency');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['has_client_portal', 'has_multi_currency', 'has_thermal_printer']);
        });
    }
};
