<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Configuración contable por empresa
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('accounting_standard', 20)->default('niif_pymes');
            $table->boolean('auto_journal_entries')->default(true);
            $table->boolean('cost_centers_enabled')->default(false);
            $table->boolean('budgets_enabled')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id']);
        });

        // 2. Plan de cuentas jerárquico
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 255);
            $table->string('account_type', 20);
            $table->string('account_nature', 10);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_parent')->default(false);
            $table->boolean('allows_movement')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('tax_form_code', 10)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'company_id', 'code']);
            $table->index(['tenant_id', 'company_id', 'account_type']);
            $table->index(['tenant_id', 'company_id', 'allows_movement']);
        });

        // 3. Períodos fiscales
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->string('period_type', 10)->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 10)->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'year', 'month']);
            $table->index(['tenant_id', 'company_id', 'status']);
        });

        // 4. Centros de costo (antes de journal_entry_lines y budget_lines que la referencian)
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 255);
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'company_id', 'code']);
        });

        // 5. Asientos contables (Libro Diario)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();
            $table->string('entry_number', 20);
            $table->date('entry_date');
            $table->string('description', 500)->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->nullableMorphs('source_document');
            $table->string('status', 10)->default('draft');
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'company_id', 'entry_number']);
            $table->index(['tenant_id', 'company_id', 'entry_date']);
            $table->index(['tenant_id', 'company_id', 'status']);
            $table->index(['tenant_id', 'source_type']);
        });

        // 6. Líneas de asientos contables
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id']);
        });

        // 7. Presupuestos
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->unsignedSmallInteger('year');
            $table->string('status', 10)->default('draft');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'company_id', 'year']);
        });

        // 8. Líneas de presupuesto
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->decimal('budgeted_amount', 14, 2)->default(0);
            $table->decimal('executed_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'budget_id', 'account_id']);
        });

        // 9. Plantillas de mapeo para asientos automáticos
        Schema::create('account_mapping_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->string('name', 255);
            $table->json('mapping_rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'document_type'], 'acct_mapping_tpl_tenant_company_doctype_idx');
        });

        // 10. Formularios tributarios generados
        Schema::create('tax_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('form_type', 20);
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('fiscal_month')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('generated_data')->nullable();
            $table->string('xml_path', 500)->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'company_id', 'form_type', 'fiscal_year'], 'tax_form_sub_tenant_company_type_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_form_submissions');
        Schema::dropIfExists('account_mapping_templates');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('accounting_settings');
    }
};
