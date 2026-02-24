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
        if (Schema::hasColumn('coupons', 'current_uses')) {
            return;
        }

        // Only run on MySQL for existing databases
        if (DB::getDriverName() === 'mysql') {
            Schema::table('coupons', function (Blueprint $table) {
                $table->renameColumn('times_used', 'current_uses');
                $table->renameColumn('valid_from', 'starts_at');
                $table->renameColumn('valid_until', 'expires_at');
            });

            DB::statement("ALTER TABLE coupons MODIFY COLUMN discount_type VARCHAR(20) NOT NULL DEFAULT 'percentage'");

            Schema::table('coupons', function (Blueprint $table) {
                $table->string('name', 255)->nullable()->after('code');
                $table->decimal('max_discount_amount', 8, 2)->nullable()->after('discount_value');
                $table->decimal('min_purchase_amount', 8, 2)->nullable()->after('max_discount_amount');
                $table->json('applicable_billing_cycles')->nullable()->after('applicable_plans');
                $table->unsignedInteger('max_uses_per_tenant')->nullable()->after('max_uses');
                $table->boolean('first_payment_only')->default(false)->after('is_active');
                $table->unsignedInteger('duration_months')->nullable()->after('first_payment_only');
                $table->json('metadata')->nullable()->after('duration_months');
            });
        }
    }

    public function down(): void
    {
        // No-op: handled by base migration
    }
};
