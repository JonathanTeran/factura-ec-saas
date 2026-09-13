<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla api_keys existía pero nunca se usó: el prefijo (`fec_` + 8 chars =
 * 12) no cabía en varchar(10), el hash no era único y no había auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key_prefix', 16)->change();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->string('last_used_ip', 45)->nullable()->after('last_used_at');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->unique('key_hash');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropUnique(['key_hash']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('last_used_ip');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key_prefix', 10)->change();
        });
    }
};
