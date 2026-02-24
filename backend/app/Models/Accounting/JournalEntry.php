<?php

namespace App\Models\Accounting;

use App\Enums\JournalEntrySource;
use App\Enums\JournalEntryStatus;
use App\Models\Tenant\Company;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'fiscal_period_id',
        'entry_number',
        'entry_date',
        'description',
        'source_type',
        'source_document_type',
        'source_document_id',
        'status',
        'total_debit',
        'total_credit',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'status' => JournalEntryStatus::class,
        'source_type' => JournalEntrySource::class,
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function sourceDocument(): MorphTo
    {
        return $this->morphTo('source_document');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // ==================== SCOPES ====================

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', JournalEntryStatus::DRAFT);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', JournalEntryStatus::POSTED);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('fiscal_period_id', $periodId);
    }

    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }

    // ==================== HELPERS ====================

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    public function recalculateTotals(): void
    {
        $this->update([
            'total_debit' => $this->lines()->sum('debit'),
            'total_credit' => $this->lines()->sum('credit'),
        ]);
    }

    public function canBePosted(): bool
    {
        return $this->status === JournalEntryStatus::DRAFT
            && $this->isBalanced()
            && $this->lines()->count() >= 2;
    }

    public function canBeVoided(): bool
    {
        return $this->status === JournalEntryStatus::POSTED;
    }
}
