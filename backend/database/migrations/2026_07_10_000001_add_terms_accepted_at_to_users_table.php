<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Constancia de aceptación de Términos y Condiciones + Política de
 * Privacidad al registrarse (requisito de buenas prácticas legales, estilo
 * legal.ecuafact.com). Nullable: los usuarios existentes no la tienen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
