<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proveedores
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('identification_type', 2);
            $table->string('identification', 20)->index();
            $table->string('business_name');
            $table->string('commercial_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_withholding_agent')->default(false);
            $table->string('accounting_account')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_purchased', 14, 2)->default(0);
            $table->date('last_purchase_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'identification']);
        });

        // Compras (Liquidaciones de compra recibidas)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 2)->default('01'); // 01=Factura recibida
            $table->string('supplier_document_number', 17); // 001-001-000000001
            $table->string('supplier_authorization', 49)->nullable();
            $table->date('issue_date');
            $table->date('authorization_date')->nullable();
            $table->decimal('subtotal_0', 14, 2)->default(0);
            $table->decimal('subtotal_5', 14, 2)->default(0);
            $table->decimal('subtotal_12', 14, 2)->default(0);
            $table->decimal('subtotal_15', 14, 2)->default(0);
            $table->decimal('subtotal_no_tax', 14, 2)->default(0);
            $table->decimal('total_discount', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status')->default('registered'); // registered, withholding_issued, paid, voided
            $table->foreignId('withholding_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
            $table->json('payment_methods')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'issue_date']);
            $table->index(['tenant_id', 'supplier_id']);
        });

        // Items de compra
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('main_code', 25)->nullable();
            $table->string('description');
            $table->decimal('quantity', 14, 6);
            $table->decimal('unit_price', 14, 6);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->string('tax_code', 1)->default('2'); // 2=IVA
            $table->string('tax_percentage_code', 4)->default('2'); // 2=12%
            $table->decimal('tax_rate', 5, 2)->default(12);
            $table->decimal('tax_value', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
