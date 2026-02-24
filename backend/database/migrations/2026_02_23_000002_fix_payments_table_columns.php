<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if columns already exist (fresh install from consolidated migration)
        if (Schema::hasColumn('payments', 'tax_amount')) {
            return;
        }

        // Only run on MySQL for existing databases
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(50) NOT NULL");

            Schema::table('payments', function (Blueprint $table) {
                $table->string('transaction_id')->nullable()->after('subscription_id');
                $table->decimal('tax_amount', 10, 2)->default(0)->after('amount');
                $table->decimal('total_amount', 10, 2)->default(0)->after('tax_amount');
                $table->string('gateway', 50)->nullable()->after('currency');
                $table->string('gateway_transaction_id')->nullable()->after('gateway_payment_id');
                $table->string('billing_name', 300)->nullable()->after('admin_notes');
                $table->string('billing_email', 255)->nullable()->after('billing_name');
                $table->string('billing_identification', 20)->nullable()->after('billing_email');
                $table->string('billing_address', 500)->nullable()->after('billing_identification');
                $table->string('billing_phone', 20)->nullable()->after('billing_address');
                $table->text('failure_reason')->nullable()->after('billing_phone');
                $table->timestamp('refunded_at')->nullable()->after('failure_reason');
                $table->decimal('refund_amount', 10, 2)->nullable()->after('refunded_at');
                $table->text('refund_reason')->nullable()->after('refund_amount');
                $table->text('notes')->nullable()->after('refund_reason');
                $table->json('metadata')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        // No-op: handled by base migration
    }
};
