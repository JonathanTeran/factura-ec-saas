<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de negocio del tenant: habilita verticales especializadas (árbitros y,
 * a futuro, otras) sin tocar el núcleo. 'generic' = facturador normal.
 * Ver docs/arbitros-vertical-spec.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('business_type', 30)->default('generic')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
