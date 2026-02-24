<?php

namespace App\Listeners;

use App\Events\PurchaseRegistered;
use App\Models\Accounting\AccountingSetting;
use App\Services\Accounting\AutoJournalEntryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryForPurchase implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AutoJournalEntryService $autoJournalEntryService,
    ) {}

    public function handle(PurchaseRegistered $event): void
    {
        $purchase = $event->purchase;
        $tenant = $purchase->tenant;

        if (!$tenant || !$tenant->has_accounting) {
            return;
        }

        $company = $purchase->company;
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
                ->generateFromPurchase($purchase);
        } catch (\Throwable $e) {
            Log::error("Error generando asiento contable para compra {$purchase->id}: {$e->getMessage()}");
        }
    }
}
