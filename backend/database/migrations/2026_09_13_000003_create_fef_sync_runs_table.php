<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial de sincronizaciones con la API pública FEF (vertical árbitros). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fef_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 20);              // schedule|manual|cli
            $table->string('status', 20);               // running|success|partial|failed
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('stats')->nullable();          // conteos de ingesta/matching/directorio
            $table->json('api_errors')->nullable();     // [{path, reason}] endpoints FEF caídos
            $table->text('error_message')->nullable();  // excepción cuando status=failed
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'started_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fef_sync_runs');
    }
};
