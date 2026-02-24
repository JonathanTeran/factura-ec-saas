<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->char('identification_type', 2);
            $table->string('identification', 20);
            $table->string('name', 300);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('total_invoiced', 14, 2)->default(0);
            $table->date('last_invoice_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'identification_type', 'identification'], 'uk_tenant_id_type');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'name']);
            $table->index('identification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
