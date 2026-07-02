<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('quote_number', 20);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'invoiced', 'expired'])->default('draft');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total_discount', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->foreignId('converted_to_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_quotes_tenant');
            $table->index(['tenant_id', 'customer_id'], 'idx_quotes_customer');
            $table->index(['tenant_id', 'status'], 'idx_quotes_status');
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 300);
            $table->decimal('quantity', 14, 4)->default(1);
            $table->decimal('unit_price', 14, 6)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_value', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
