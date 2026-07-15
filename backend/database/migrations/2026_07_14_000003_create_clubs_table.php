<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo público de clubes (GLOBAL). `name` es el nombre completo oficial que
 * se imprime en el concepto de la factura. Ver docs/arbitros-vertical-spec.md §3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // nombre completo oficial
            $table->string('short_name')->nullable();     // alias corto (solo UI/búsqueda)
            $table->string('category')->nullable();
            $table->string('external_ref')->nullable();   // slug/id en la API FEF
            $table->string('logo_path')->nullable();
            $table->timestamps();

            $table->index('external_ref');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
