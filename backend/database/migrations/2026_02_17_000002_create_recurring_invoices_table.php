<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('emission_point_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('created_by')->constrained('users');

            // Frecuencia
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'bimonthly', 'quarterly', 'semiannual', 'annual']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_issue_date');

            // Estado
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');

            // Plantilla del documento
            $table->json('items');
            $table->json('payment_methods')->nullable();
            $table->json('additional_info')->nullable();
            $table->text('notes')->nullable();
            $table->string('currency', 10)->default('DOLAR');

            // Contadores
            $table->unsignedInteger('total_issued')->default(0);
            $table->unsignedInteger('max_issues')->nullable();
            $table->timestamp('last_issued_at')->nullable();

            // Notificaciones
            $table->boolean('notify_before_issue')->default(true);
            $table->unsignedTinyInteger('notify_days_before')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'next_issue_date']);
        });

        // Add foreign key constraint to electronic_documents
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->foreign('recurring_invoice_id')
                ->references('id')
                ->on('recurring_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->dropForeign(['recurring_invoice_id']);
        });

        Schema::dropIfExists('recurring_invoices');
    }
};
