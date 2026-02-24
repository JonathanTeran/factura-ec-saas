<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('electronic_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('main_code', 25);
            $table->string('aux_code', 25)->nullable();
            $table->text('description');
            $table->decimal('quantity', 14, 6);
            $table->decimal('unit_price', 14, 6);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);

            // Impuestos
            $table->char('tax_code', 1)->default('2');
            $table->string('tax_percentage_code', 4);
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_base', 14, 2);
            $table->decimal('tax_value', 14, 2);

            // ICE
            $table->string('ice_code', 10)->nullable();
            $table->decimal('ice_rate', 5, 2)->nullable();
            $table->decimal('ice_value', 14, 2)->nullable();

            // Orden
            $table->integer('sort_order')->default(0);
            $table->json('additional_details')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('electronic_document_id');
        });

        Schema::create('withholding_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('electronic_document_id')->constrained()->cascadeOnDelete();

            // Documento sustento
            $table->string('support_doc_code', 2);
            $table->string('support_doc_number', 17);
            $table->date('support_doc_date');
            $table->decimal('support_doc_total', 14, 2)->nullable();
            $table->string('support_reason_code', 2)->nullable();

            // Retención
            $table->enum('tax_type', ['renta', 'iva']);
            $table->string('retention_code', 10);
            $table->decimal('tax_base', 14, 2);
            $table->decimal('retention_rate', 5, 2);
            $table->decimal('retained_value', 14, 2);

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('electronic_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_details');
        Schema::dropIfExists('document_items');
    }
};
