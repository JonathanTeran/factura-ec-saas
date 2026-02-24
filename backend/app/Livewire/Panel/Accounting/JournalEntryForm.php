<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\JournalEntryStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\Company;
use App\Services\Accounting\AccountingService;
use Livewire\Component;

class JournalEntryForm extends Component
{
    public ?int $entryId = null;
    public int $companyId = 0;
    public string $entryDate = '';
    public string $description = '';
    public array $lines = [];

    public string $accountSearch = '';
    public ?int $searchingLineIndex = null;
    public array $accountResults = [];

    public function mount(?int $entry = null): void
    {
        $this->entryDate = now()->format('Y-m-d');

        $company = Company::where('tenant_id', auth()->user()->tenant_id)->first();
        if ($company) {
            $this->companyId = $company->id;
        }

        if ($entry) {
            $this->entryId = $entry;
            $je = JournalEntry::where('tenant_id', auth()->user()->tenant_id)
                ->with('lines.account')
                ->findOrFail($entry);

            if ($je->status !== JournalEntryStatus::DRAFT) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Solo se pueden editar asientos en borrador.',
                ]);
                $this->redirect(route('panel.accounting.journal-entries.show', $je->id), navigate: true);
                return;
            }

            $this->companyId = $je->company_id;
            $this->entryDate = $je->entry_date->format('Y-m-d');
            $this->description = $je->description ?? '';

            $this->lines = $je->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'description' => $line->description ?? '',
            ])->toArray();
        }

        if (empty($this->lines)) {
            $this->addLine();
            $this->addLine();
        }
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'account_id' => null,
            'account_code' => '',
            'account_name' => '',
            'debit' => 0,
            'credit' => 0,
            'description' => '',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        if (count($this->lines) < 2) {
            $this->addLine();
        }
    }

    public function searchAccounts(int $index): void
    {
        $this->searchingLineIndex = $index;

        if (strlen($this->accountSearch) < 2) {
            $this->accountResults = [];
            return;
        }

        $this->accountResults = Account::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('allows_movement', true)
            ->where(function ($q) {
                $q->where('code', 'like', "%{$this->accountSearch}%")
                    ->orWhere('name', 'like', "%{$this->accountSearch}%");
            })
            ->orderBy('code')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
            ])
            ->toArray();
    }

    public function selectAccount(int $index, int $accountId): void
    {
        $account = Account::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->findOrFail($accountId);

        $this->lines[$index]['account_id'] = $account->id;
        $this->lines[$index]['account_code'] = $account->code;
        $this->lines[$index]['account_name'] = $account->name;
        $this->accountSearch = '';
        $this->accountResults = [];
        $this->searchingLineIndex = null;
    }

    public function clearAccountSearch(): void
    {
        $this->accountSearch = '';
        $this->accountResults = [];
        $this->searchingLineIndex = null;
    }

    public function getTotalDebitProperty(): float
    {
        return round(collect($this->lines)->sum('debit'), 2);
    }

    public function getTotalCreditProperty(): float
    {
        return round(collect($this->lines)->sum('credit'), 2);
    }

    public function getDifferenceProperty(): float
    {
        return round(abs($this->totalDebit - $this->totalCredit), 2);
    }

    public function getIsBalancedProperty(): bool
    {
        return bccomp((string) $this->totalDebit, (string) $this->totalCredit, 2) === 0;
    }

    public function save(AccountingService $service): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'entryDate' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        if (!$this->isBalanced) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El asiento no esta balanceado. Debito y credito deben ser iguales.',
            ]);
            return;
        }

        $validLines = collect($this->lines)->filter(function ($line) {
            return $line['account_id'] && ($line['debit'] > 0 || $line['credit'] > 0);
        })->values()->toArray();

        if (count($validLines) < 2) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El asiento debe tener al menos 2 lineas con movimiento.',
            ]);
            return;
        }

        $company = Company::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->companyId);

        $data = [
            'entry_date' => $this->entryDate,
            'description' => $this->description,
        ];

        $lineData = collect($validLines)->map(fn ($line) => [
            'account_id' => $line['account_id'],
            'debit' => (float) $line['debit'],
            'credit' => (float) $line['credit'],
            'description' => $line['description'] ?: null,
        ])->toArray();

        if ($this->entryId) {
            $entry = JournalEntry::where('tenant_id', auth()->user()->tenant_id)
                ->findOrFail($this->entryId);
            $service->forCompany($company)->updateJournalEntry($entry, $data, $lineData);
            $message = 'Asiento actualizado correctamente.';
        } else {
            $entry = $service->forCompany($company)->createJournalEntry($data, $lineData);
            $message = 'Asiento creado correctamente.';
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        $this->redirect(route('panel.accounting.journal-entries.show', $entry->id), navigate: true);
    }

    public function getCompaniesProperty()
    {
        return Company::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('business_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.panel.accounting.journal-entry-form', [
            'companies' => $this->companies,
            'totalDebit' => $this->totalDebit,
            'totalCredit' => $this->totalCredit,
            'difference' => $this->difference,
            'isBalanced' => $this->isBalanced,
        ])->layout('layouts.tenant', [
            'title' => $this->entryId ? 'Editar Asiento' : 'Nuevo Asiento Contable',
        ]);
    }
}
