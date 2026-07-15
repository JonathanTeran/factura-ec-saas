<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo público de campeonatos (GLOBAL, sin tenant_id). Lo mantiene el
 * scraper/ingesta de la API FEF y el backoffice. Ver docs/arbitros-vertical-spec.md §3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();     // formativa|segunda|liga_pro|femenino|copa…
            $table->string('season')->nullable();        // año/temporada
            $table->string('external_ref')->nullable();  // hierarchy_id / path en la API FEF
            $table->unsignedTinyInteger('invoice_window_start_day')->nullable();
            $table->unsignedTinyInteger('invoice_window_end_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('external_ref');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championships');
    }
};
