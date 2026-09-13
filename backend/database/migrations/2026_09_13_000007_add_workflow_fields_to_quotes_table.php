<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proformas: numeración única por tenant (antes count()+1 podía repetir
 * números al borrar) y trazabilidad del flujo (enviada a quién y cuándo,
 * aceptada/rechazada/convertida).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Duplicados heredados: se renumeran con sufijo antes del índice único.
        $duplicates = DB::table('quotes')
            ->select('tenant_id', 'quote_number')
            ->groupBy('tenant_id', 'quote_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $ids = DB::table('quotes')
                ->where('tenant_id', $dup->tenant_id)
                ->where('quote_number', $dup->quote_number)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids->slice(1)->values() as $index => $id) {
                DB::table('quotes')->where('id', $id)->update([
                    'quote_number' => $dup->quote_number.'-'.($index + 2),
                ]);
            }
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('converted_to_document_id');
            $table->string('sent_to', 190)->nullable()->after('sent_at');
            $table->timestamp('accepted_at')->nullable()->after('sent_to');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->timestamp('converted_at')->nullable()->after('rejected_at');
            $table->unique(['tenant_id', 'quote_number'], 'uq_quotes_tenant_number');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('uq_quotes_tenant_number');
            $table->dropColumn(['sent_at', 'sent_to', 'accepted_at', 'rejected_at', 'converted_at']);
        });
    }
};
