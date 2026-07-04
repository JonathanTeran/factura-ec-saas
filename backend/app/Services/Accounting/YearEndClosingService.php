<?php

namespace App\Services\Accounting;

use App\Enums\JournalEntrySource;
use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Tenant\Company;

/**
 * Asiento de cierre del ejercicio: salda todas las cuentas de resultado
 * (ingresos, costos y gastos) y lleva la diferencia a Utilidad del ejercicio
 * (3.07.01) o Pérdida del ejercicio (3.07.02).
 */
class YearEndClosingService
{
    private Company $company;

    public function __construct(
        private readonly AccountingService $accountingService,
    ) {}

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        $this->accountingService->forCompany($company);

        return $this;
    }

    public function generateClosingEntry(FiscalPeriod $annualPeriod): ?JournalEntry
    {
        if ($annualPeriod->period_type !== 'annual') {
            return null;
        }

        $year = (int) $annualPeriod->year;

        // Idempotencia: un solo cierre por año
        $existing = JournalEntry::where('company_id', $this->company->id)
            ->where('source_type', JournalEntrySource::CLOSING)
            ->whereYear('entry_date', $year)
            ->first();

        if ($existing) {
            return $existing;
        }

        $balances = $this->resultAccountBalances($year);

        if ($balances->isEmpty()) {
            return null;
        }

        $lines = [];
        $result = 0.0; // ingresos - costos - gastos

        foreach ($balances as $balance) {
            $net = round((float) $balance->credit_total - (float) $balance->debit_total, 2);

            if ($net === 0.0) {
                continue;
            }

            // Saldar la cuenta: si quedó acreedora (ingresos) se debita, y viceversa
            $lines[] = [
                'account_id' => $balance->account_id,
                'debit' => $net > 0 ? $net : 0,
                'credit' => $net < 0 ? abs($net) : 0,
                'description' => 'Cierre del ejercicio',
            ];

            $result += $net;
        }

        if ($lines === []) {
            return null;
        }

        $result = round($result, 2);

        if ($result > 0) {
            $utilidad = $this->findAccount('3.07.01');
            $lines[] = ['account_id' => $utilidad->id, 'debit' => 0, 'credit' => $result, 'description' => 'Utilidad del ejercicio'];
        } elseif ($result < 0) {
            $perdida = $this->findAccount('3.07.02');
            $lines[] = ['account_id' => $perdida->id, 'debit' => abs($result), 'credit' => 0, 'description' => 'Pérdida del ejercicio'];
        }

        $entry = $this->accountingService->createJournalEntry([
            'entry_date' => $annualPeriod->end_date ?? "{$year}-12-31",
            'description' => "Cierre del ejercicio {$year}",
            'source_type' => JournalEntrySource::CLOSING,
        ], $lines);

        $this->accountingService->postJournalEntry($entry);

        return $entry;
    }

    /**
     * Saldos netos de las cuentas de resultado del año (solo asientos
     * contabilizados, excluyendo cierres previos).
     */
    private function resultAccountBalances(int $year)
    {
        return JournalEntryLine::query()
            ->selectRaw('journal_entry_lines.account_id, SUM(journal_entry_lines.debit) as debit_total, SUM(journal_entry_lines.credit) as credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.company_id', $this->company->id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.source_type', '!=', JournalEntrySource::CLOSING->value)
            ->whereYear('journal_entries.entry_date', $year)
            ->whereIn('chart_of_accounts.account_type', ['ingreso', 'costo', 'gasto'])
            ->groupBy('journal_entry_lines.account_id')
            ->get();
    }

    private function findAccount(string $code): Account
    {
        return Account::where('company_id', $this->company->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
