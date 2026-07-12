<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nuevas tarifas de IVA: 8% (diferenciado/turismo, codigoPorcentaje SRI '8')
 * y 13% (codigoPorcentaje SRI '10'). El comprobante necesita su propia base
 * imponible por tarifa, igual que subtotal_5/12/15.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->decimal('subtotal_8', 12, 2)->default(0)->after('subtotal_15');
            $table->decimal('subtotal_13', 12, 2)->default(0)->after('subtotal_8');
        });
    }

    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->dropColumn(['subtotal_8', 'subtotal_13']);
        });
    }
};
