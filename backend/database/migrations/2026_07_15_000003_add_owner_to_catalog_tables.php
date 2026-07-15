<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que un árbitro cree campeonatos/clubes "para sí mismo" cuando faltan
 * en el catálogo oficial. tenant_id NULL = oficial (sync FEF / admin), visible
 * para todos; tenant_id definido = personal, visible solo para ese árbitro.
 * El super admin puede promover un personal a oficial (tenant_id → null).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['championships', 'clubs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('tenant_id')->nullable()->after('id')
                    ->constrained('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['championships', 'clubs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
