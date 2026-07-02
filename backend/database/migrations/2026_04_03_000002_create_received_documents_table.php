<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->char('document_type', 2)->default('01')->comment('01=factura,03=liquidacion,04=NC,05=ND,06=guia,07=retencion');
            $table->char('access_key', 49)->nullable()->unique();
            $table->string('authorization_number', 49)->nullable();
            $table->date('authorization_date')->nullable();
            $table->string('issuer_ruc', 13);
            $table->string('issuer_name', 300);
            $table->date('issue_date');
            $table->decimal('subtotal_0', 14, 2)->default(0);
            $table->decimal('subtotal_5', 14, 2)->default(0);
            $table->decimal('subtotal_12', 14, 2)->default(0);
            $table->decimal('subtotal_15', 14, 2)->default(0);
            $table->decimal('subtotal_no_tax', 14, 2)->default(0);
            $table->decimal('total_discount', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('expense_category', 50)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->string('xml_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'idx_rdoc_tenant');
            $table->index(['tenant_id', 'issue_date'], 'idx_rdoc_date');
            $table->index(['tenant_id', 'issuer_ruc'], 'idx_rdoc_issuer');
            $table->index(['tenant_id', 'expense_category'], 'idx_rdoc_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_documents');
    }
};
