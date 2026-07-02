<?php

namespace App\Services\Accounting;

use App\Enums\AccountingStandard;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Tenant\Company;
use Illuminate\Support\Facades\Log;

/**
 * Activa la contabilidad básica "de serie" para una empresa: plan de cuentas
 * NIIF PYMES, periodos fiscales del año en curso y asientos automáticos.
 *
 * Idempotente y no destructivo: nunca pisa una configuración contable
 * existente ni vuelve a sembrar un plan de cuentas ya creado.
 */
class BasicAccountingActivator
{
    public function __construct(
        private readonly ChartOfAccountsService $chartService,
        private readonly FiscalPeriodService $periodService,
    ) {}

    public function activate(Company $company): void
    {
        $tenant = $company->tenant;

        if (! $tenant->has_accounting) {
            $tenant->update(['has_accounting' => true]);
        }

        AccountingSetting::firstOrCreate(
            [
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
            ],
            [
                'accounting_standard' => AccountingStandard::NIIF_PYMES,
                'auto_journal_entries' => true,
            ]
        );

        if (! Account::where('company_id', $company->id)->exists()) {
            $this->chartService
                ->forCompany($company)
                ->seedDefaultAccounts(AccountingStandard::NIIF_PYMES);
        }

        $year = now()->year;
        if (! FiscalPeriod::where('company_id', $company->id)->where('year', $year)->exists()) {
            $this->periodService->forCompany($company)->createPeriodsForYear($year);
        }
    }

    /**
     * Variante tolerante a fallos: la activación contable nunca debe romper
     * el flujo que la dispara (p. ej. completar el onboarding).
     */
    public function activateQuietly(Company $company): void
    {
        try {
            $this->activate($company);
        } catch (\Throwable $e) {
            Log::error('Fallo al activar contabilidad básica', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
