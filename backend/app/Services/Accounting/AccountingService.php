<?php

namespace App\Services\Accounting;

use App\Enums\JournalEntrySource;
use App\Enums\JournalEntryStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Tenant\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function createJournalEntry(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            // Generar número secuencial
            $entryNumber = $this->generateEntryNumber();

            // Determinar período fiscal
            $period = FiscalPeriod::where('company_id', $this->company->id)
                ->where('period_type', 'monthly')
                ->whereDate('start_date', '<=', $data['entry_date'])
                ->whereDate('end_date', '>=', $data['entry_date'])
                ->first();

            if ($period && !$period->allowsEntries()) {
                throw new \RuntimeException('El periodo fiscal esta cerrado. No se pueden agregar asientos.');
            }

            $entry = JournalEntry::create([
                'tenant_id' => $this->company->tenant_id,
                'company_id' => $this->company->id,
                'fiscal_period_id' => $period?->id,
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
                'source_type' => $data['source_type'] ?? JournalEntrySource::MANUAL,
                'source_document_type' => $data['source_document_type'] ?? null,
                'source_document_id' => $data['source_document_id'] ?? null,
                'status' => JournalEntryStatus::DRAFT,
                'created_by' => auth()->id(),
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $this->company->tenant_id,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);

                $totalDebit += $line['debit'] ?? 0;
                $totalCredit += $line['credit'] ?? 0;
            }

            $entry->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            return $entry->fresh()->load('lines.account');
        });
    }

    public function updateJournalEntry(JournalEntry $entry, array $data, array $lines): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::DRAFT) {
            throw new \RuntimeException('Solo se pueden editar asientos en estado borrador.');
        }

        return DB::transaction(function () use ($entry, $data, $lines) {
            $entry->update([
                'entry_date' => $data['entry_date'] ?? $entry->entry_date,
                'description' => $data['description'] ?? $entry->description,
            ]);

            // Recrear líneas
            $entry->lines()->delete();

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $this->company->tenant_id,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);

                $totalDebit += $line['debit'] ?? 0;
                $totalCredit += $line['credit'] ?? 0;
            }

            $entry->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            return $entry->fresh()->load('lines.account');
        });
    }

    public function postJournalEntry(JournalEntry $entry): JournalEntry
    {
        if (!$entry->canBePosted()) {
            throw new \RuntimeException('El asiento no puede ser contabilizado. Verifique que este balanceado y tenga al menos 2 lineas.');
        }

        $entry->update([
            'status' => JournalEntryStatus::POSTED,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function voidJournalEntry(JournalEntry $entry, string $reason): JournalEntry
    {
        if (!$entry->canBeVoided()) {
            throw new \RuntimeException('Solo se pueden anular asientos contabilizados.');
        }

        $entry->update([
            'status' => JournalEntryStatus::VOIDED,
            'voided_by' => auth()->id(),
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);

        return $entry->fresh();
    }

    public function getGeneralLedger(int $accountId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('status', JournalEntryStatus::POSTED);
                if ($fromDate) {
                    $q->where('entry_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->where('entry_date', '<=', $toDate);
                }
            })
            ->with(['journalEntry', 'costCenter'])
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry->entry_date);

        $runningBalance = 0;
        $account = Account::find($accountId);

        return $query->map(function ($line) use (&$runningBalance, $account) {
            if ($account->account_nature === 'debit') {
                $runningBalance += (float) $line->debit - (float) $line->credit;
            } else {
                $runningBalance += (float) $line->credit - (float) $line->debit;
            }

            return [
                'date' => $line->journalEntry->entry_date->format('Y-m-d'),
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => round($runningBalance, 2),
                'cost_center' => $line->costCenter?->name,
            ];
        })->values();
    }

    public function getSubsidiaryLedger(int $companyId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) use ($fromDate, $toDate) {
            $movements = $this->getGeneralLedger($account->id, $fromDate, $toDate);

            if ($movements->isEmpty()) {
                return null;
            }

            return [
                'account' => $account,
                'movements' => $movements,
            ];
        })->filter()->values();
    }

    private function generateEntryNumber(): string
    {
        $year = now()->year;
        $prefix = "AC-{$year}-";

        $lastEntry = JournalEntry::where('company_id', $this->company->id)
            ->where('entry_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(entry_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) str_replace($prefix, '', $lastEntry->entry_number);
            return $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }

        return $prefix . '000001';
    }
}
