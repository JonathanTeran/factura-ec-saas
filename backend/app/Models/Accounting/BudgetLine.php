<?php

namespace App\Models\Accounting;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'budget_id',
        'account_id',
        'cost_center_id',
        'month',
        'budgeted_amount',
        'executed_amount',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'executed_amount' => 'decimal:2',
        'month' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    // ==================== HELPERS ====================

    public function getRemainingAmount(): float
    {
        return (float) bcsub((string) $this->budgeted_amount, (string) $this->executed_amount, 2);
    }

    public function getExecutionPercentage(): float
    {
        if ($this->budgeted_amount <= 0) {
            return 0;
        }

        return round(($this->executed_amount / $this->budgeted_amount) * 100, 2);
    }

    public function isOverBudget(): bool
    {
        return $this->executed_amount > $this->budgeted_amount;
    }
}
