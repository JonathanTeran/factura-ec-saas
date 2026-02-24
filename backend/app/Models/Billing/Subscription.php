<?php

namespace App\Models\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'coupon_code',
        'status',
        'billing_cycle',
        'amount',
        'discount_percent',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'cancellation_reason',
        'auto_renew',
        'payment_method',
        'gateway_subscription_id',
        'gateway_customer_id',
        'last_payment_at',
        'next_payment_at',
        'failed_payments_count',
        'metadata',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'last_payment_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopePastDue($query)
    {
        return $query->where('status', SubscriptionStatus::PAST_DUE);
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', SubscriptionStatus::TRIALING);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    // ==================== HELPERS ====================

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatus::PAST_DUE;
    }

    public function isTrialing(): bool
    {
        return $this->status === SubscriptionStatus::TRIALING;
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED;
    }

    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function daysUntilExpiration(): int
    {
        if (!$this->ends_at) {
            return PHP_INT_MAX;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function daysOnTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED,
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function resume(): void
    {
        if ($this->isCanceled() && !$this->hasEnded()) {
            $this->update([
                'status' => SubscriptionStatus::ACTIVE,
                'canceled_at' => null,
                'cancellation_reason' => null,
                'auto_renew' => true,
            ]);
        }
    }

    public function markAsPastDue(): void
    {
        $this->update([
            'status' => SubscriptionStatus::PAST_DUE,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);
    }

    public function renew(): void
    {
        $endsAt = $this->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();

        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'failed_payments_count' => 0,
        ]);
    }

    public function getBillingCycleLabel(): string
    {
        return match ($this->billing_cycle) {
            'monthly' => 'Mensual',
            'yearly' => 'Anual',
            default => ucfirst($this->billing_cycle),
        };
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the base price from the plan based on billing cycle.
     */
    public function getPriceAttribute(): float
    {
        if (!$this->plan) {
            return (float) $this->amount;
        }

        return (float) ($this->billing_cycle === 'yearly'
            ? $this->plan->price_yearly
            : $this->plan->price_monthly);
    }

    /**
     * Get the discount amount applied.
     */
    public function getDiscountAmountAttribute(): float
    {
        if ($this->discount_percent > 0) {
            return round($this->price * ($this->discount_percent / 100), 2);
        }

        return round(max(0, $this->price - (float) $this->amount), 2);
    }

    /**
     * Get the final price after discount (same as amount stored in DB).
     */
    public function getFinalPriceAttribute(): float
    {
        return (float) $this->amount;
    }
}
