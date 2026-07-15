<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participación del árbitro en un partido = unidad "pendiente por facturar"
 * (POR TENANT). Enlaza el partido del catálogo, el campeonato, el rol y la
 * factura emitida. Ver docs/arbitros-vertical-spec.md §3.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officiated_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('football_match_id')->nullable()->constrained('football_matches')->nullOnDelete();
            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();
            $table->foreignId('home_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->foreignId('away_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->date('match_date');
            $table->string('role');                       // arbitro|asistente_1|asistente_2|cuarto|var|comisario|delegado
            $table->decimal('fee', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending|queued|invoiced|blocked_window
            $table->foreignId('electronic_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
            $table->timestamp('invoiced_at')->nullable();
            $table->string('source')->default('scraper'); // scraper|manual
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            // Un árbitro no debería tener dos participaciones del mismo partido con el mismo rol.
            $table->unique(['tenant_id', 'football_match_id', 'role'], 'officiated_unique_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officiated_matches');
    }
};
