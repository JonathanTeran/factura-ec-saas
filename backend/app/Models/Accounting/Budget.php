<?php

namespace App\Models\Accounting;

use App\Enums\BudgetStatus;
use App\Models\Tenant\Company;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'year',
        'status',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status' => BudgetStatus::class,
        'total_amount' => 'decimal:2',
        'year' => 'integer',
        'approved_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::ACTIVE);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    // ==================== HELPERS ====================

    public function getExecutionPercentage(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        $executed = $this->lines()->sum('executed_amount');

        return round(($executed / $this->total_amount) * 100, 2);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total_amount' => $this->lines()->sum('budgeted_amount'),
        ]);
    }
}
