<?php

namespace App\Jobs;

use App\Services\RecurringInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('documents');
    }

    public function handle(RecurringInvoiceService $service): void
    {
        Log::info('Processing recurring invoices...');

        $results = $service->processAllDue();

        Log::info('Recurring invoices processed', $results);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessRecurringInvoicesJob failed: {$exception->getMessage()}");
    }
}
