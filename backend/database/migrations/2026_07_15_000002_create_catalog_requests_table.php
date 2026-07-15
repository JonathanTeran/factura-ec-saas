<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de catálogo del vertical de árbitros (§5.5 del spec): cuando un
 * árbitro no encuentra un campeonato o club en el selector, lo solicita desde
 * la app y el super admin lo aprueba (creándolo en el catálogo) o lo rechaza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type');                        // championship|club
            $table->string('name');                        // nombre solicitado
            $table->string('comment')->nullable();         // contexto del árbitro
            $table->string('status')->default('pending');  // pending|approved|rejected
            $table->string('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_requests');
    }
};
