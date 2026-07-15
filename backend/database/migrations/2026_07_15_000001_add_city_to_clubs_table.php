<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciudad/provincia del club, mostrada entre paréntesis en la UI para
 * distinguir clubes homónimos. Se rellena best-effort desde los campeonatos
 * provinciales y es editable por el super admin. NO se usa en el concepto de
 * la factura (formato FEF intacto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->string('city')->nullable()->after('short_name');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
