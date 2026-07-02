<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('category', 50);
            $table->string('description', 300);
            $table->string('issuer_ruc', 13)->nullable();
            $table->string('issuer_name', 300)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->date('issue_date');
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_pexp_tenant');
            $table->index(['user_id', 'fiscal_year'], 'idx_pexp_user_year');
            $table->index(['tenant_id', 'category'], 'idx_pexp_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_expenses');
    }
};
