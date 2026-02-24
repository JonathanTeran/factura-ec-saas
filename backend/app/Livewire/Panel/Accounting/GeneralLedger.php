<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Accounting\Account;
use App\Models\Tenant\Company;
use App\Services\Accounting\AccountingService;
use Livewire\Component;

class GeneralLedger extends Component
{
    public int $companyId = 0;
    public ?int $accountId = null;
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $accountSearch = '';
    public array $accountResults = [];
    public ?string $selectedAccountCode = null;
    public ?string $selectedAccountName = null;

    protected $queryString = [
        'accountId' => ['except' => null],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        $company = Company::where('tenant_id', auth()->user()->tenant_id)->first();
        if ($company) {
            $this->companyId = $company->id;
        }

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        if ($this->accountId) {
            $account = Account::find($this->accountId);
            if ($account) {
                $this->selectedAccountCode = $account->code;
                $this->selectedAccountName = $account->name;
            }
        }
    }

    public function updatedAccountSearch(): void
    {
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

    public function selectAccount(int $accountId): void
    {
        $account = Account::where('company_id', $this->companyId)
            ->findOrFail($accountId);

        $this->accountId = $account->id;
        $this->selectedAccountCode = $account->code;
        $this->selectedAccountName = $account->name;
        $this->accountSearch = '';
        $this->accountResults = [];
    }

    public function clearAccount(): void
    {
        $this->accountId = null;
        $this->selectedAccountCode = null;
        $this->selectedAccountName = null;
        $this->accountSearch = '';
        $this->accountResults = [];
    }

    public function getLedgerProperty()
    {
        if (!$this->accountId) {
            return collect();
        }

        $service = app(AccountingService::class);
        $company = Company::findOrFail($this->companyId);

        return $service->forCompany($company)->getGeneralLedger(
            $this->accountId,
            $this->dateFrom ?: null,
            $this->dateTo ?: null
        );
    }

    public function getTotalsProperty(): array
    {
        $ledger = $this->ledger;

        return [
            'debit' => round($ledger->sum('debit'), 2),
            'credit' => round($ledger->sum('credit'), 2),
            'balance' => $ledger->isNotEmpty() ? $ledger->last()['balance'] : 0,
        ];
    }

    public function render()
    {
        return view('livewire.panel.accounting.general-ledger', [
            'ledger' => $this->ledger,
            'totals' => $this->totals,
        ])->layout('layouts.tenant', ['title' => 'Libro Mayor']);
    }
}
