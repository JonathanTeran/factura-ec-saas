<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            // Precios
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_yearly', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Límites
            $table->integer('max_documents_per_month')->default(10)->comment('-1 = ilimitado');
            $table->integer('max_users')->default(1);
            $table->integer('max_companies')->default(1);
            $table->integer('max_emission_points')->default(1);

            // Features
            $table->boolean('has_electronic_signature')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_inventory')->default(false);
            $table->boolean('has_pos')->default(false);
            $table->boolean('has_recurring_invoices')->default(false);
            $table->boolean('has_proformas')->default(false);
            $table->boolean('has_ats')->default(false);
            $table->boolean('has_thermal_printer')->default(false);
            $table->boolean('has_advanced_reports')->default(false);
            $table->boolean('has_whitelabel_ride')->default(false);
            $table->boolean('has_webhooks')->default(false);
            $table->boolean('has_client_portal')->default(false);
            $table->boolean('has_multi_currency')->default(false);
            $table->boolean('has_accountant_access')->default(false);
            $table->boolean('has_ai_categorization')->default(false);

            // Soporte
            $table->enum('support_level', ['community', 'email', 'priority', 'dedicated'])->default('community');
            $table->integer('support_response_hours')->default(72);

            // Control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('trial_days')->default(14);

            // Metadata
            $table->json('features_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
