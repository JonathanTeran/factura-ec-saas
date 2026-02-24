<?php

namespace App\Models\Tenant;

use App\Enums\PosSessionStatus;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'emission_point_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'total_transactions',
        'total_cash',
        'total_card',
        'total_transfer',
        'total_other',
        'total_sales',
        'status',
        'closing_notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => PosSessionStatus::class,
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_transfer' => 'decimal:2',
        'total_other' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function emissionPoint(): BelongsTo
    {
        return $this->belongsTo(EmissionPoint::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', PosSessionStatus::OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', PosSessionStatus::CLOSED);
    }

    // ==================== HELPERS ====================

    public function isOpen(): bool
    {
        return $this->status === PosSessionStatus::OPEN;
    }

    public function close(float $closingAmount, ?string $notes = null): void
    {
        $expected = $this->opening_amount + $this->total_cash;

        $this->update([
            'status' => PosSessionStatus::CLOSED,
            'closed_by' => auth()->id(),
            'closing_amount' => $closingAmount,
            'expected_amount' => $expected,
            'difference' => $closingAmount - $expected,
            'closing_notes' => $notes,
            'closed_at' => now(),
        ]);
    }

    public function addTransaction(string $paymentMethod, float $amount): void
    {
        $this->increment('total_transactions');

        match ($paymentMethod) {
            'cash' => $this->increment('total_cash', $amount),
            'card' => $this->increment('total_card', $amount),
            'transfer' => $this->increment('total_transfer', $amount),
            default => $this->increment('total_other', $amount),
        };

        $this->increment('total_sales', $amount);
    }
}
