<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "blocked_window" dejó de ser un estado guardado: la ventana FEF se evalúa en
 * vivo (pendiente + hoy fuera del 1–20). Los partidos que quedaron marcados
 * como blocked_window vuelven a "pending" para que se muestren y facturen
 * normalmente cuando la ventana esté abierta.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('officiated_matches')
            ->where('status', 'blocked_window')
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        // Sin reversa: el estado blocked_window quedó deprecado.
    }
};
