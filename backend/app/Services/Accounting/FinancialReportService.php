<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalEntryStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Tenant\Company;
use Illuminate\Support\Collection;

class FinancialReportService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Balance de Comprobación
     */
    public function getTrialBalance(?string $fromDate = null, ?string $toDate = null): array
    {
        $accounts = Account::where('company_id', $this->company->id)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $data = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $movements = $this->getAccountMovements($account->id, $fromDate, $toDate);

            if ($movements['debit'] == 0 && $movements['credit'] == 0) {
                continue;
            }

            $balance = $account->account_nature === 'debit'
                ? $movements['debit'] - $movements['credit']
                : $movements['credit'] - $movements['debit'];

            $row = [
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type->label(),
                'debit_movements' => $movements['debit'],
                'credit_movements' => $movements['credit'],
                'debit_balance' => $balance > 0 ? $balance : 0,
                'credit_balance' => $balance < 0 ? abs($balance) : 0,
            ];

            $data[] = $row;
            $totalDebit += $row['debit_balance'];
            $totalCredit += $row['credit_balance'];
        }

        return [
            'data' => $data,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
            ],
        ];
    }

    /**
     * Estado de Situación Financiera (Balance General)
     */
    public function getBalanceSheet(?string $toDate = null): array
    {
        $date = $toDate ?? now()->format('Y-m-d');

        $activos = $this->getGroupBalance(AccountType::ACTIVO, null, $date);
        $pasivos = $this->getGroupBalance(AccountType::PASIVO, null, $date);
        $patrimonio = $this->getGroupBalance(AccountType::PATRIMONIO, null, $date);

        // Resultado del ejercicio = Ingresos - Costos - Gastos
        $ingresos = $this->getGroupTotal(AccountType::INGRESO, null, $date);
        $costos = $this->getGroupTotal(AccountType::COSTO, null, $date);
        $gastos = $this->getGroupTotal(AccountType::GASTO, null, $date);
        $resultadoEjercicio = $ingresos - $costos - $gastos;

        $totalActivos = collect($activos)->sum('balance');
        $totalPasivos = collect($pasivos)->sum('balance');
        $totalPatrimonio = collect($patrimonio)->sum('balance') + $resultadoEjercicio;

        return [
            'date' => $date,
            'activos' => $activos,
            'pasivos' => $pasivos,
            'patrimonio' => $patrimonio,
            'resultado_ejercicio' => round($resultadoEjercicio, 2),
            'totals' => [
                'total_activos' => round($totalActivos, 2),
                'total_pasivos' => round($totalPasivos, 2),
                'total_patrimonio' => round($totalPatrimonio, 2),
                'total_pasivo_patrimonio' => round($totalPasivos + $totalPatrimonio, 2),
            ],
        ];
    }

    /**
     * Estado de Resultados Integral
     */
    public function getIncomeStatement(?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ?? now()->startOfYear()->format('Y-m-d');
        $to = $toDate ?? now()->format('Y-m-d');

        $ingresos = $this->getGroupBalance(AccountType::INGRESO, $from, $to);
        $costos = $this->getGroupBalance(AccountType::COSTO, $from, $to);
        $gastos = $this->getGroupBalance(AccountType::GASTO, $from, $to);

        $totalIngresos = collect($ingresos)->sum('balance');
        $totalCostos = collect($costos)->sum('balance');
        $totalGastos = collect($gastos)->sum('balance');

        $utilidadBruta = $totalIngresos - $totalCostos;
        $utilidadOperacional = $utilidadBruta - $totalGastos;

        return [
            'period' => ['from' => $from, 'to' => $to],
            'ingresos' => $ingresos,
            'costos' => $costos,
            'gastos' => $gastos,
            'totals' => [
                'total_ingresos' => round($totalIngresos, 2),
                'total_costos' => round($totalCostos, 2),
                'total_gastos' => round($totalGastos, 2),
                'utilidad_bruta' => round($utilidadBruta, 2),
                'utilidad_operacional' => round($utilidadOperacional, 2),
            ],
        ];
    }

    /**
     * Estado de Flujo de Efectivo (método directo simplificado)
     */
    public function getCashFlowStatement(?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ?? now()->startOfYear()->format('Y-m-d');
        $to = $toDate ?? now()->format('Y-m-d');

        // Obtener movimientos de cuentas de efectivo (1.01.01)
        $cashAccounts = Account::where('company_id', $this->company->id)
            ->where('code', 'like', '1.01.01%')
            ->where('allows_movement', true)
            ->pluck('id');

        $movements = JournalEntryLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($q) use ($from, $to) {
                $q->where('status', JournalEntryStatus::POSTED)
                    ->whereBetween('entry_date', [$from, $to]);
            })
            ->with('journalEntry')
            ->get();

        $inflows = $movements->sum('debit');
        $outflows = $movements->sum('credit');

        return [
            'period' => ['from' => $from, 'to' => $to],
            'inflows' => round($inflows, 2),
            'outflows' => round($outflows, 2),
            'net_cash_flow' => round($inflows - $outflows, 2),
        ];
    }

    private function getGroupBalance(AccountType $type, ?string $fromDate, ?string $toDate): array
    {
        $accounts = Account::where('company_id', $this->company->id)
            ->where('account_type', $type)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) use ($fromDate, $toDate) {
            $balance = $account->getBalance($fromDate, $toDate);

            if (abs($balance) < 0.01) {
                return null;
            }

            return [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => round(abs($balance), 2),
            ];
        })->filter()->values()->toArray();
    }

    private function getGroupTotal(AccountType $type, ?string $fromDate, ?string $toDate): float
    {
        $accounts = Account::where('company_id', $this->company->id)
            ->where('account_type', $type)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->get();

        return $accounts->sum(fn ($account) => abs($account->getBalance($fromDate, $toDate)));
    }

    private function getAccountMovements(int $accountId, ?string $fromDate, ?string $toDate): array
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
            });

        return [
            'debit' => round((float) $query->sum('debit'), 2),
            'credit' => round((float) $query->sum('credit'), 2),
        ];
    }
}
