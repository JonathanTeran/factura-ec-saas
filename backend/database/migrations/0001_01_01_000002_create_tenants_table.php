<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('owner_email');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled', 'expired'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->foreignId('current_plan_id')->nullable()->constrained('plans');
            $table->enum('subscription_status', ['trialing', 'active', 'past_due', 'cancelled', 'incomplete'])->default('trialing');

            // Límites del plan actual (cache denormalizado para performance)
            $table->integer('max_documents_per_month')->default(10);
            $table->integer('max_users')->default(1);
            $table->integer('max_companies')->default(1);
            $table->integer('max_emission_points')->default(1);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_inventory')->default(false);
            $table->boolean('has_pos')->default(false);
            $table->boolean('has_recurring_invoices')->default(false);
            $table->boolean('has_advanced_reports')->default(false);
            $table->boolean('has_whitelabel_ride')->default(false);

            // Contadores del período actual
            $table->integer('documents_this_month')->default(0);
            $table->date('documents_month_reset_at')->nullable();

            // Referidos
            $table->string('referral_code', 20)->unique()->nullable();
            $table->foreignId('referred_by_tenant_id')->nullable()->constrained('tenants');

            // Metadata
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
