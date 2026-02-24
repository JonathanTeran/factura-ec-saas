<?php

namespace App\Services\Accounting;

use App\Enums\AccountingStandard;
use App\Enums\AccountType;
use App\Models\Accounting\Account;
use App\Models\Tenant\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function seedDefaultAccounts(AccountingStandard $standard): void
    {
        $file = $standard === AccountingStandard::NIIF_FULL
            ? 'chart_of_accounts_niif_full'
            : 'chart_of_accounts_niif_pymes';

        $accounts = require database_path("seeders/data/{$file}.php");

        DB::transaction(function () use ($accounts) {
            $parentMap = [];

            foreach ($accounts as $accountData) {
                $account = Account::create([
                    'tenant_id' => $this->company->tenant_id,
                    'company_id' => $this->company->id,
                    'code' => $accountData['code'],
                    'name' => $accountData['name'],
                    'account_type' => $accountData['account_type'],
                    'account_nature' => $accountData['account_nature'],
                    'parent_id' => isset($accountData['parent_code'])
                        ? ($parentMap[$accountData['parent_code']] ?? null)
                        : null,
                    'level' => $accountData['level'],
                    'is_parent' => $accountData['is_parent'] ?? false,
                    'allows_movement' => $accountData['allows_movement'] ?? true,
                    'tax_form_code' => $accountData['tax_form_code'] ?? null,
                ]);

                $parentMap[$accountData['code']] = $account->id;
            }
        });
    }

    public function createAccount(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // Si tiene padre, heredar tipo y naturaleza
            if (!empty($data['parent_id'])) {
                $parent = Account::findOrFail($data['parent_id']);
                $data['account_type'] = $data['account_type'] ?? $parent->account_type->value;
                $data['account_nature'] = $data['account_nature'] ?? $parent->account_nature;
                $data['level'] = $parent->level + 1;

                // Marcar padre como is_parent
                if (!$parent->is_parent) {
                    $parent->update(['is_parent' => true, 'allows_movement' => false]);
                }
            }

            return Account::create([
                'tenant_id' => $this->company->tenant_id,
                'company_id' => $this->company->id,
                ...$data,
            ]);
        });
    }

    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function deleteAccount(Account $account): void
    {
        if ($account->children()->exists()) {
            throw new \RuntimeException('No se puede eliminar una cuenta que tiene subcuentas.');
        }

        if ($account->journalEntryLines()->exists()) {
            throw new \RuntimeException('No se puede eliminar una cuenta con movimientos contables.');
        }

        $account->delete();
    }

    public function getTree(?int $companyId = null): Collection
    {
        $cid = $companyId ?? $this->company->id;

        return Account::where('company_id', $cid)
            ->whereNull('parent_id')
            ->with('children.children.children.children')
            ->orderBy('code')
            ->get();
    }

    public function getAccountBalance(Account $account, ?string $fromDate = null, ?string $toDate = null): float
    {
        return $account->getBalance($fromDate, $toDate);
    }

    public function getTrialBalance(int $companyId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function (Account $account) use ($fromDate, $toDate) {
            $balance = $account->getBalance($fromDate, $toDate);

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type,
                'account_nature' => $account->account_nature,
                'debit_balance' => $balance > 0 && $account->account_nature === 'debit' ? $balance : ($balance < 0 && $account->account_nature === 'credit' ? abs($balance) : 0),
                'credit_balance' => $balance > 0 && $account->account_nature === 'credit' ? $balance : ($balance < 0 && $account->account_nature === 'debit' ? abs($balance) : 0),
            ];
        })->filter(fn ($item) => $item['debit_balance'] != 0 || $item['credit_balance'] != 0);
    }

    public function searchAccounts(string $query, ?int $companyId = null): Collection
    {
        $cid = $companyId ?? $this->company->id;

        return Account::where('company_id', $cid)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit(50)
            ->get();
    }
}
