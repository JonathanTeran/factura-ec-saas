<?php

namespace App\Jobs;

use App\Services\RecurringInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Lote diario (06:00): emite las recurrentes vencidas y envía los
 * recordatorios previos. Ver RecurringInvoiceService.
 */
class ProcessRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('documents');
    }

    public function handle(RecurringInvoiceService $service): void
    {
        Log::info('Recurrentes: procesando vencidas...');

        $results = $service->processAllDue();
        $results['reminders'] = $service->sendReminders();

        Log::info('Recurrentes: lote terminado', $results);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessRecurringInvoicesJob failed: {$exception->getMessage()}");
    }
}
