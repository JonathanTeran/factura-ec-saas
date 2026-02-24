<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status', 50)->default('trialing');
            $table->string('billing_cycle', 20)->default('monthly');

            // Fechas
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Pago
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_gateway', 50)->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();
            $table->unsignedInteger('failed_payments_count')->default(0);

            // Montos
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('ends_at');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained();

            // Transaction tracking
            $table->string('transaction_id')->nullable();
            $table->string('invoice_number', 50)->nullable();

            // Status
            $table->string('status', 50)->default('pending');
            $table->string('payment_method', 50);

            // Amounts
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Gateway
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();

            // Description
            $table->string('description', 500)->nullable();

            // Transfer approval fields
            $table->string('transfer_receipt_path')->nullable();
            $table->text('transfer_reference')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('admin_notes')->nullable();

            // Billing info
            $table->string('billing_name', 300)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_identification', 20)->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('billing_phone', 20)->nullable();

            // Failure tracking
            $table->text('failure_reason')->nullable();

            // Refund fields
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();

            // Notes & metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Payment timestamp
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('paid_at');
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255)->nullable();
            $table->string('description')->nullable();
            $table->string('discount_type', 20)->default('percentage');
            $table->decimal('discount_value', 8, 2);
            $table->decimal('max_discount_amount', 8, 2)->nullable();
            $table->decimal('min_purchase_amount', 8, 2)->nullable();
            $table->json('applicable_plans')->nullable();
            $table->json('applicable_billing_cycles')->nullable();
            $table->integer('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_tenant')->nullable();
            $table->integer('current_uses')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('first_payment_only')->default(false);
            $table->unsignedInteger('duration_months')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_tenant_id')->constrained('tenants');
            $table->foreignId('referred_tenant_id')->constrained('tenants');
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_method')->nullable();
            $table->string('payout_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
