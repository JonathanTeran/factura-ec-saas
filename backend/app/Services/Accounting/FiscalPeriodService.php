<?php

namespace App\Services\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Enums\JournalEntrySource;
use App\Enums\JournalEntryStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FiscalPeriodService
{
    private Company $company;
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        $this->accountingService->forCompany($company);
        return $this;
    }

    /**
     * Crear períodos para un año completo (12 mensuales + 1 anual)
     */
    public function createPeriodsForYear(int $year): array
    {
        $periods = [];

        DB::transaction(function () use ($year, &$periods) {
            // 12 períodos mensuales
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();

                $periods[] = FiscalPeriod::firstOrCreate(
                    [
                        'tenant_id' => $this->company->tenant_id,
                        'company_id' => $this->company->id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'period_type' => 'monthly',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => FiscalPeriodStatus::OPEN,
                    ]
                );
            }

            // Período anual
            $periods[] = FiscalPeriod::firstOrCreate(
                [
                    'tenant_id' => $this->company->tenant_id,
                    'company_id' => $this->company->id,
                    'year' => $year,
                    'month' => null,
                ],
                [
                    'period_type' => 'annual',
                    'start_date' => Carbon::create($year, 1, 1),
                    'end_date' => Carbon::create($year, 12, 31),
                    'status' => FiscalPeriodStatus::OPEN,
                ]
            );
        });

        return $periods;
    }

    /**
     * Cerrar un período mensual
     */
    public function closePeriod(FiscalPeriod $period): FiscalPeriod
    {
        if ($period->status !== FiscalPeriodStatus::OPEN) {
            throw new \RuntimeException('Solo se pueden cerrar periodos abiertos.');
        }

        // Verificar que todos los asientos estén contabilizados o anulados
        $draftEntries = JournalEntry::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $period->id)
            ->where('status', JournalEntryStatus::DRAFT)
            ->count();

        if ($draftEntries > 0) {
            throw new \RuntimeException("Existen {$draftEntries} asientos en borrador. Contabilice o anule todos los asientos antes de cerrar el periodo.");
        }

        $period->update([
            'status' => FiscalPeriodStatus::CLOSED,
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return $period->fresh();
    }

    /**
     * Bloquear un período (irreversible excepto por super admin)
     */
    public function lockPeriod(FiscalPeriod $period): FiscalPeriod
    {
        if ($period->status !== FiscalPeriodStatus::CLOSED) {
            throw new \RuntimeException('Solo se pueden bloquear periodos cerrados.');
        }

        $period->update(['status' => FiscalPeriodStatus::LOCKED]);

        return $period->fresh();
    }

    /**
     * Reabrir un período cerrado
     */
    public function reopenPeriod(FiscalPeriod $period): FiscalPeriod
    {
        if (!in_array($period->status, [FiscalPeriodStatus::CLOSED])) {
            throw new \RuntimeException('Solo se pueden reabrir periodos cerrados (no bloqueados).');
        }

        $period->update([
            'status' => FiscalPeriodStatus::OPEN,
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return $period->fresh();
    }

    /**
     * Generar asiento de cierre anual (resultados → patrimonio)
     */
    public function generateClosingEntry(int $year): ?JournalEntry
    {
        $fromDate = "{$year}-01-01";
        $toDate = "{$year}-12-31";

        // Obtener saldos de cuentas de resultados
        $incomeAccounts = Account::where('company_id', $this->company->id)
            ->whereIn('account_type', ['ingreso'])
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->get();

        $expenseAccounts = Account::where('company_id', $this->company->id)
            ->whereIn('account_type', ['costo', 'gasto'])
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->get();

        $lines = [];

        // Cerrar ingresos (débito para cerrar crédito acumulado)
        foreach ($incomeAccounts as $account) {
            $balance = abs($account->getBalance($fromDate, $toDate));
            if ($balance > 0) {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $balance,
                    'credit' => 0,
                    'description' => "Cierre {$account->code} - {$account->name}",
                ];
            }
        }

        // Cerrar gastos/costos (crédito para cerrar débito acumulado)
        foreach ($expenseAccounts as $account) {
            $balance = abs($account->getBalance($fromDate, $toDate));
            if ($balance > 0) {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => 0,
                    'credit' => $balance,
                    'description' => "Cierre {$account->code} - {$account->name}",
                ];
            }
        }

        if (empty($lines)) {
            return null;
        }

        // Calcular resultado
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');
        $resultado = $totalDebit - $totalCredit;

        // Cuenta de resultados del ejercicio (3.07.01 o similar)
        $resultadoAccount = Account::where('company_id', $this->company->id)
            ->where('code', 'like', '3.07%')
            ->where('allows_movement', true)
            ->first();

        if (!$resultadoAccount) {
            $resultadoAccount = Account::where('company_id', $this->company->id)
                ->where('account_type', 'patrimonio')
                ->where('allows_movement', true)
                ->orderByDesc('code')
                ->first();
        }

        if ($resultadoAccount) {
            if ($resultado > 0) {
                // Utilidad: crédito a patrimonio
                $lines[] = [
                    'account_id' => $resultadoAccount->id,
                    'debit' => 0,
                    'credit' => $resultado,
                    'description' => 'Resultado del ejercicio (Utilidad)',
                ];
            } else {
                // Pérdida: débito a patrimonio
                $lines[] = [
                    'account_id' => $resultadoAccount->id,
                    'debit' => abs($resultado),
                    'credit' => 0,
                    'description' => 'Resultado del ejercicio (Perdida)',
                ];
            }
        }

        return $this->accountingService->createJournalEntry([
            'entry_date' => $toDate,
            'description' => "Asiento de cierre - Ejercicio fiscal {$year}",
            'source_type' => JournalEntrySource::CLOSING,
        ], $lines);
    }

    /**
     * Generar asiento de apertura para el nuevo año
     */
    public function generateOpeningEntry(int $year): ?JournalEntry
    {
        $prevYear = $year - 1;
        $toDate = "{$prevYear}-12-31";

        // Obtener saldos de cuentas de balance (activo, pasivo, patrimonio)
        $balanceAccounts = Account::where('company_id', $this->company->id)
            ->whereIn('account_type', ['activo', 'pasivo', 'patrimonio'])
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->get();

        $lines = [];

        foreach ($balanceAccounts as $account) {
            $balance = $account->getBalance(null, $toDate);

            if (abs($balance) < 0.01) {
                continue;
            }

            if ($account->account_nature === 'debit') {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $balance > 0 ? $balance : 0,
                    'credit' => $balance < 0 ? abs($balance) : 0,
                    'description' => "Apertura {$account->code}",
                ];
            } else {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $balance < 0 ? abs($balance) : 0,
                    'credit' => $balance > 0 ? $balance : 0,
                    'description' => "Apertura {$account->code}",
                ];
            }
        }

        if (empty($lines)) {
            return null;
        }

        return $this->accountingService->createJournalEntry([
            'entry_date' => "{$year}-01-01",
            'description' => "Asiento de apertura - Ejercicio fiscal {$year}",
            'source_type' => JournalEntrySource::OPENING,
        ], $lines);
    }
}
