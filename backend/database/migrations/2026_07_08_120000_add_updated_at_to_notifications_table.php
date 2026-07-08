<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla se creó sin updated_at, pero DatabaseNotification (canal
 * "database") lo incluye en cada insert → PDOException "Unknown column
 * 'updated_at'". El job de la notificación moría ANTES de pasar por el canal
 * mail, así que los correos (ej. comprobante autorizado / reenvío) nunca
 * llegaban.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
