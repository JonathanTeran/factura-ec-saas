<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if columns already exist (fresh install from consolidated migration)
        if (Schema::hasColumn('payments', 'transfer_receipt_path')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('transfer_receipt_path')->nullable()->after('gateway_response');
            $table->text('transfer_reference')->nullable()->after('transfer_receipt_path');
            $table->foreignId('approved_by')->nullable()->after('transfer_reference')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('failed_at')->nullable()->after('approved_at');
            $table->text('admin_notes')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('payments', 'transfer_receipt_path')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'transfer_receipt_path',
                'transfer_reference',
                'approved_by',
                'approved_at',
                'failed_at',
                'admin_notes',
            ]);
        });
    }
};
