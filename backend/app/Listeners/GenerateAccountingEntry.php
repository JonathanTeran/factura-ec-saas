<?php

namespace App\Listeners;

use App\Events\DocumentAuthorized;
use App\Models\Accounting\AccountingSetting;
use App\Services\Accounting\AutoJournalEntryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly AutoJournalEntryService $autoJournalEntryService,
    ) {}

    public function handle(DocumentAuthorized $event): void
    {
        $document = $event->document;
        $tenant = $document->tenant;

        // Solo ejecutar si el módulo de contabilidad está habilitado
        if (!$tenant || !$tenant->has_accounting) {
            return;
        }

        $company = $document->company;
        if (!$company) {
            return;
        }

        // Verificar que auto_journal_entries esté habilitado
        $settings = AccountingSetting::where('tenant_id', $tenant->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$settings || !$settings->auto_journal_entries) {
            return;
        }

        try {
            $this->autoJournalEntryService
                ->forCompany($company)
                ->generateFromDocument($document);
        } catch (\Throwable $e) {
            Log::error("Error generando asiento contable para documento {$document->id}: {$e->getMessage()}");
        }
    }
}
