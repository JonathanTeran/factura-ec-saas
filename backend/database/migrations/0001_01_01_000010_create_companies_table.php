<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('ruc', 13);
            $table->string('business_name', 300)->comment('Razón social');
            $table->string('trade_name', 300)->nullable()->comment('Nombre comercial');
            $table->string('legal_representative')->nullable();
            $table->enum('taxpayer_type', ['natural', 'juridical', 'rise'])->default('natural');
            $table->boolean('obligated_accounting')->default(false);
            $table->boolean('special_taxpayer')->default(false);
            $table->string('special_taxpayer_number', 20)->nullable();
            $table->string('retention_agent_number', 20)->nullable();
            $table->enum('rimpe_type', ['none', 'emprendedor', 'negocio_popular'])->default('none');
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email');
            $table->string('logo_path', 500)->nullable();

            // SRI Config
            $table->char('sri_environment', 1)->default('1')->comment('1=pruebas, 2=producción');

            // Firma electrónica (almacenada encriptada)
            $table->string('signature_path', 500)->nullable();
            $table->text('signature_password')->nullable();
            $table->timestamp('signature_expires_at')->nullable();
            $table->string('signature_issuer')->nullable();
            $table->string('signature_subject')->nullable();

            // Estado
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'ruc']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
