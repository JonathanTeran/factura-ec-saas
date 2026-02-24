<?php

namespace App\Models\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Models\Tenant\Company;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalPeriod extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'year',
        'month',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'status' => FiscalPeriodStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'year' => 'integer',
        'month' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    // ==================== SCOPES ====================

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', FiscalPeriodStatus::OPEN);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month)
            ->where('period_type', 'monthly');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    // ==================== HELPERS ====================

    public function allowsEntries(): bool
    {
        return $this->status->allowsEntries();
    }

    public function getLabel(): string
    {
        if ($this->period_type === 'annual') {
            return "Año {$this->year}";
        }

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($months[$this->month] ?? '') . " {$this->year}";
    }
}
