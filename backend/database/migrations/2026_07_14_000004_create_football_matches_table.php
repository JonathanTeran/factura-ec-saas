<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo público de partidos (GLOBAL), poblado por la ingesta de la API FEF.
 * `officials` guarda los nombres publicados (terna + cuarto). Un partido SIEMPRE
 * tiene campeonato (§5.5). Ver docs/arbitros-vertical-spec.md §3.1 y §6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();
            $table->foreignId('home_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->foreignId('away_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->date('match_date');
            $table->string('stage')->nullable();          // etapa/jornada/ronda
            $table->string('external_ref')->nullable();   // match_id (uuid) en la API FEF
            $table->json('officials')->nullable();        // {center, assistant_1, assistant_2, fourth}
            $table->string('source')->default('scraper'); // scraper|manual
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('external_ref');
            $table->index(['match_date', 'championship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_matches');
    }
};
