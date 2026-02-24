<?php

namespace App\Models\Billing;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_tenant_id',
        'referred_tenant_id',
        'payment_id',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'paid_at',
        'payout_method',
        'payout_reference',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_REJECTED = 'rejected';

    // ==================== RELACIONES ====================

    public function referrerTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referredTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_tenant_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForReferrer($query, int $tenantId)
    {
        return $query->where('referrer_tenant_id', $tenantId);
    }

    public function scopePayable($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    // ==================== HELPERS ====================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'notes' => $reason,
        ]);
    }

    public function markAsPaid(string $method, string $reference): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payout_method' => $method,
            'payout_reference' => $reference,
        ]);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_PAID => 'Pagada',
            self::STATUS_REJECTED => 'Rechazada',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Calcula y crea la comisión para un pago de referido.
     */
    public static function createFromPayment(Payment $payment, float $commissionRate = 10.0): ?static
    {
        $referredTenant = $payment->tenant;

        if (!$referredTenant || !$referredTenant->referred_by_tenant_id) {
            return null;
        }

        // Verificar si ya existe comisión para este pago
        if (static::where('payment_id', $payment->id)->exists()) {
            return null;
        }

        return static::create([
            'referrer_tenant_id' => $referredTenant->referred_by_tenant_id,
            'referred_tenant_id' => $referredTenant->id,
            'payment_id' => $payment->id,
            'commission_rate' => $commissionRate,
            'commission_amount' => round($payment->total_amount * ($commissionRate / 100), 2),
            'currency' => $payment->currency,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Obtiene el total de comisiones pendientes de pago para un tenant.
     */
    public static function getTotalPayableForReferrer(int $tenantId): float
    {
        return static::forReferrer($tenantId)
            ->payable()
            ->sum('commission_amount');
    }
}
