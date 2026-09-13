<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturas recurrentes: nombre descriptivo, emisión automática al SRI,
 * último error del lote (para mostrarlo en el panel sin revisar logs) y
 * control de recordatorios previos (una sola vez por fecha de emisión).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->string('name', 120)->nullable()->after('created_by');
            $table->boolean('auto_send')->default(true)->after('notify_days_before');
            $table->text('last_error')->nullable()->after('auto_send');
            $table->timestamp('last_error_at')->nullable()->after('last_error');
            $table->date('reminder_sent_for')->nullable()->after('last_error_at');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn(['name', 'auto_send', 'last_error', 'last_error_at', 'reminder_sent_for']);
        });
    }
};
