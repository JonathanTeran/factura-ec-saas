<?php

namespace App\Services\Accounting;

use App\Enums\BudgetStatus;
use App\Models\Accounting\Budget;
use App\Models\Accounting\BudgetLine;
use App\Models\Tenant\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function createBudget(array $data, array $lines): Budget
    {
        return DB::transaction(function () use ($data, $lines) {
            $budget = Budget::create([
                'tenant_id' => $this->company->tenant_id,
                'company_id' => $this->company->id,
                'name' => $data['name'],
                'year' => $data['year'],
                'status' => BudgetStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            foreach ($lines as $line) {
                BudgetLine::create([
                    'tenant_id' => $this->company->tenant_id,
                    'budget_id' => $budget->id,
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'month' => $line['month'],
                    'budgeted_amount' => $line['budgeted_amount'],
                    'executed_amount' => 0,
                ]);

                $totalAmount += $line['budgeted_amount'];
            }

            $budget->update(['total_amount' => $totalAmount]);

            return $budget->fresh()->load('lines.account');
        });
    }

    public function updateBudget(Budget $budget, array $data, array $lines): Budget
    {
        if (!in_array($budget->status, [BudgetStatus::DRAFT, BudgetStatus::APPROVED])) {
            throw new \RuntimeException('Solo se pueden editar presupuestos en borrador o aprobados.');
        }

        return DB::transaction(function () use ($budget, $data, $lines) {
            $budget->update([
                'name' => $data['name'] ?? $budget->name,
                'year' => $data['year'] ?? $budget->year,
                'notes' => $data['notes'] ?? $budget->notes,
            ]);

            $budget->lines()->delete();

            $totalAmount = 0;

            foreach ($lines as $line) {
                BudgetLine::create([
                    'tenant_id' => $this->company->tenant_id,
                    'budget_id' => $budget->id,
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'month' => $line['month'],
                    'budgeted_amount' => $line['budgeted_amount'],
                    'executed_amount' => $line['executed_amount'] ?? 0,
                ]);

                $totalAmount += $line['budgeted_amount'];
            }

            $budget->update(['total_amount' => $totalAmount]);

            return $budget->fresh()->load('lines.account');
        });
    }

    public function approveBudget(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::DRAFT) {
            throw new \RuntimeException('Solo se pueden aprobar presupuestos en borrador.');
        }

        $budget->update([
            'status' => BudgetStatus::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $budget->fresh();
    }

    public function activateBudget(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::APPROVED) {
            throw new \RuntimeException('Solo se pueden activar presupuestos aprobados.');
        }

        $budget->update(['status' => BudgetStatus::ACTIVE]);

        return $budget->fresh();
    }

    public function closeBudget(Budget $budget): Budget
    {
        $budget->update(['status' => BudgetStatus::CLOSED]);
        return $budget->fresh();
    }

    public function getBudgetExecution(Budget $budget): Collection
    {
        return $budget->lines()
            ->with(['account', 'costCenter'])
            ->get()
            ->groupBy('account_id')
            ->map(function ($lines) {
                $account = $lines->first()->account;

                $months = [];
                foreach ($lines as $line) {
                    $months[$line->month] = [
                        'budgeted' => (float) $line->budgeted_amount,
                        'executed' => (float) $line->executed_amount,
                        'remaining' => $line->getRemainingAmount(),
                        'percentage' => $line->getExecutionPercentage(),
                        'over_budget' => $line->isOverBudget(),
                    ];
                }

                return [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'months' => $months,
                    'total_budgeted' => $lines->sum('budgeted_amount'),
                    'total_executed' => $lines->sum('executed_amount'),
                ];
            })->values();
    }

    public function checkBudgetAlert(int $accountId, int $month, float $amount): ?string
    {
        $activeBudget = Budget::where('company_id', $this->company->id)
            ->where('status', BudgetStatus::ACTIVE)
            ->where('year', now()->year)
            ->first();

        if (!$activeBudget) {
            return null;
        }

        $line = BudgetLine::where('budget_id', $activeBudget->id)
            ->where('account_id', $accountId)
            ->where('month', $month)
            ->first();

        if (!$line) {
            return null;
        }

        $newExecuted = $line->executed_amount + $amount;

        if ($newExecuted > $line->budgeted_amount) {
            $overage = $newExecuted - $line->budgeted_amount;
            return "Alerta: El monto excede el presupuesto por \${$overage}";
        }

        $percentage = ($newExecuted / $line->budgeted_amount) * 100;
        if ($percentage >= 90) {
            return "Advertencia: El presupuesto esta al {$percentage}% de ejecucion.";
        }

        return null;
    }
}
