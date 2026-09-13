<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Directorio de árbitros detectados en los partidos publicados por la FEF
 * (derivado de football_matches.officials; la FEF no expone un padrón).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fef_referees', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // tal como lo publica la FEF
            $table->string('normalized_name')->unique();  // RefereeMatcher::normalize()
            $table->unsignedInteger('matches_count')->default(0);
            $table->json('roles')->nullable();            // {center: n, assistant_1: n, assistant_2: n, fourth: n}
            $table->date('first_seen_at')->nullable();
            $table->date('last_seen_at')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete(); // cuenta árbitro vinculada
            $table->timestamps();

            $table->index('last_seen_at');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fef_referees');
    }
};
