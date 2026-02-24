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
        if (Schema::hasColumn('subscriptions', 'cancellation_reason')) {
            return;
        }

        // Only run on MySQL for existing databases
        if (DB::getDriverName() === 'mysql') {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('cancelled_at', 'canceled_at');
            });

            Schema::table('subscriptions', function (Blueprint $table) {
                $table->text('cancellation_reason')->nullable()->after('canceled_at');
                $table->boolean('auto_renew')->default(true)->after('currency');
                $table->string('payment_method', 50)->nullable()->after('auto_renew');
                $table->timestamp('last_payment_at')->nullable()->after('payment_method');
                $table->timestamp('next_payment_at')->nullable()->after('last_payment_at');
                $table->unsignedInteger('failed_payments_count')->default(0)->after('next_payment_at');
            });

            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'trialing'");
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT 'monthly'");
        }
    }

    public function down(): void
    {
        // No-op: handled by base migration
    }
};
