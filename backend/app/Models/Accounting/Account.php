<?php

namespace App\Models\Accounting;

use App\Enums\AccountType;
use App\Models\Tenant\Company;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'code',
        'name',
        'account_type',
        'account_nature',
        'parent_id',
        'level',
        'is_parent',
        'allows_movement',
        'is_active',
        'tax_form_code',
        'description',
    ];

    protected $casts = [
        'account_type' => AccountType::class,
        'is_parent' => 'boolean',
        'allows_movement' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDetailAccounts(Builder $query): Builder
    {
        return $query->where('allows_movement', true);
    }

    public function scopeByType(Builder $query, AccountType $type): Builder
    {
        return $query->where('account_type', $type);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    // ==================== HELPERS ====================

    public function isMovable(): bool
    {
        return $this->allows_movement && $this->is_active;
    }

    public function getBalance(?string $fromDate = null, ?string $toDate = null): float
    {
        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'posted');
                if ($fromDate) {
                    $q->where('entry_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->where('entry_date', '<=', $toDate);
                }
            });

        $totalDebit = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        // Naturaleza deudora: saldo = débitos - créditos
        // Naturaleza acreedora: saldo = créditos - débitos
        if ($this->account_nature === 'debit') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }

    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }
}
