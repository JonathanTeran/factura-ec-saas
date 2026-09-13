<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `has_proformas` existía en `plans` pero no se denormalizaba al tenant, por
 * lo que la API de proformas nunca se podía restringir por plan. Hoy todos
 * los planes la incluyen: default true para no bloquear a nadie existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('has_proformas')->default(true)->after('has_recurring_invoices');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('has_proformas');
        });
    }
};
