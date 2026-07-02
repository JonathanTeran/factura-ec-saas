<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event', 50);
            $table->string('description', 500);
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id', 'idx_alog_tenant');
            $table->index('user_id', 'idx_alog_user');
            $table->index(['subject_type', 'subject_id'], 'idx_alog_subject');
            $table->index(['tenant_id', 'event'], 'idx_alog_event');
            $table->index('created_at', 'idx_alog_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
