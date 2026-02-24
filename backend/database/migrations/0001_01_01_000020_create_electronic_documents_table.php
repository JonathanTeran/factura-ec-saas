<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('emission_point_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('created_by')->constrained('users');

            // Identificación SRI
            $table->char('document_type', 2);
            $table->char('environment', 1);
            $table->char('series', 7);
            $table->string('sequential', 9);
            $table->char('access_key', 49)->nullable();

            // Estado del flujo SRI
            $table->enum('status', [
                'draft',
                'processing',
                'signed',
                'sent',
                'authorized',
                'rejected',
                'failed',
                'voided'
            ])->default('draft');
            $table->string('authorization_number', 49)->nullable();
            $table->timestamp('authorization_date')->nullable();

            // Montos
            $table->decimal('subtotal_no_tax', 14, 2)->default(0);
            $table->decimal('subtotal_0', 14, 2)->default(0);
            $table->decimal('subtotal_5', 14, 2)->default(0);
            $table->decimal('subtotal_12', 14, 2)->default(0);
            $table->decimal('subtotal_15', 14, 2)->default(0);
            $table->decimal('total_discount', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('total_ice', 14, 2)->default(0);
            $table->decimal('tip', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Archivos (S3 paths)
            $table->string('xml_unsigned_path', 500)->nullable();
            $table->string('xml_signed_path', 500)->nullable();
            $table->string('xml_authorized_path', 500)->nullable();
            $table->string('ride_pdf_path', 500)->nullable();

            // SRI Communication Log
            $table->json('sri_response')->nullable();
            $table->json('sri_errors')->nullable();
            $table->unsignedTinyInteger('sri_attempts')->default(0);
            $table->timestamp('last_sri_attempt_at')->nullable();

            // Documento relacionado (NC, ND, Retención)
            $table->foreignId('related_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
            $table->char('related_document_type', 2)->nullable();
            $table->string('related_document_number', 17)->nullable();
            $table->date('related_document_date')->nullable();

            // Envío al cliente
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamp('whatsapp_sent_at')->nullable();

            // Pagos
            $table->json('payment_methods')->nullable();

            // Metadata
            $table->json('additional_info')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 10)->default('DOLAR');
            $table->text('notes')->nullable();

            // Factura recurrente
            $table->foreignId('recurring_invoice_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ÍNDICES CRÍTICOS PARA PERFORMANCE
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'document_type', 'issue_date']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'issue_date']);
            $table->index(['company_id', 'document_type']);
            $table->index('access_key');
            $table->index(['status', 'sri_attempts']);
            $table->index('authorization_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_documents');
    }
};
