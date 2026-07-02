<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // electronic_documents: elimina filesort en listado principal
        // (tenant_id, issue_date, created_at) cubre ORDER BY issue_date DESC, created_at DESC
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->index(['tenant_id', 'issue_date', 'created_at'], 'ed_tenant_date_sort_index');
        });

        // subscriptions: activeSubscription() hace WHERE tenant_id + status='active'
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'subs_tenant_status_index');
        });

        // received_documents: listado filtra por tenant_id + issue_date
        Schema::table('received_documents', function (Blueprint $table) {
            $table->index(['tenant_id', 'issue_date'], 'rd_tenant_date_index');
        });

        // support_tickets: listado filtra por tenant_id + status
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'created_at'], 'st_tenant_status_date_index');
        });

        // personal_expenses: summary query agrupa por tenant_id + user_id + fiscal_year
        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'fiscal_year'], 'pe_tenant_user_year_index');
        });
    }

    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->dropIndex('ed_tenant_date_sort_index');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subs_tenant_status_index');
        });
        Schema::table('received_documents', function (Blueprint $table) {
            $table->dropIndex('rd_tenant_date_index');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('st_tenant_status_date_index');
        });
        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->dropIndex('pe_tenant_user_year_index');
        });
    }
};
