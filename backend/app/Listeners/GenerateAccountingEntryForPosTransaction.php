<?php

namespace App\Listeners;

use App\Events\PosTransactionCompleted;
use App\Models\Accounting\AccountingSetting;
use App\Services\Accounting\AutoJournalEntryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryForPosTransaction implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AutoJournalEntryService $autoJournalEntryService,
    ) {}

    public function handle(PosTransactionCompleted $event): void
    {
        $transaction = $event->transaction;
        $tenant = $transaction->tenant;

        if (!$tenant || !$tenant->has_accounting) {
            return;
        }

        $company = $transaction->session?->company;
        if (!$company) {
            return;
        }

        $settings = AccountingSetting::where('tenant_id', $tenant->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$settings || !$settings->auto_journal_entries) {
            return;
        }

        try {
            $this->autoJournalEntryService
                ->forCompany($company)
                ->generateFromPosTransaction($transaction);
        } catch (\Throwable $e) {
            Log::error("Error generando asiento contable para transaccion POS {$transaction->id}: {$e->getMessage()}");
        }
    }
}
