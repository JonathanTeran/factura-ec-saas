<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'recurring:process {--reminders-only : Solo envía los recordatorios previos, sin emitir}';

    protected $description = 'Emite las facturas recurrentes vencidas y envía los recordatorios previos (mismo trabajo que el lote diario de las 06:00)';

    public function handle(RecurringInvoiceService $service): int
    {
        $results = $this->option('reminders-only')
            ? ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]
            : $service->processAllDue();

        $results['reminders'] = $service->sendReminders();

        $this->table(
            ['Emitidas', 'Enviadas al SRI', 'Fallidas', 'Omitidas', 'Recordatorios'],
            [[$results['processed'], $results['sent'], $results['failed'], $results['skipped'], $results['reminders']]]
        );

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
