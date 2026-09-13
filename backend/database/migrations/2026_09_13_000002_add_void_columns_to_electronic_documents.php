<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DocumentController@void guardaba voided_at/void_reason en columnas que no
 * existían (la asignación masiva las descartaba en silencio).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('authorization_date');
            $table->string('void_reason', 300)->nullable()->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
