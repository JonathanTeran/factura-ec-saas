<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'applicable_plans',
        'applicable_billing_cycles',
        'max_uses',
        'max_uses_per_tenant',
        'current_uses',
        'is_active',
        'starts_at',
        'expires_at',
        'first_payment_only',
        'duration_months',
        'metadata',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'applicable_plans' => 'array',
        'applicable_billing_cycles' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'first_payment_only' => 'boolean',
        'metadata' => 'array',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coupon) {
            if (!$coupon->code) {
                $coupon->code = static::generateCode();
            }
        });
    }

    // ==================== RELACIONES ====================

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'coupon_code', 'code');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('current_uses', '<', 'max_uses');
            });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    // ==================== HELPERS ====================

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasReachedMaxUses(): bool
    {
        return $this->max_uses && $this->current_uses >= $this->max_uses;
    }

    public function canBeUsedByTenant(int $tenantId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->max_uses_per_tenant) {
            $usedByTenant = $this->subscriptions()
                ->where('tenant_id', $tenantId)
                ->count();

            if ($usedByTenant >= $this->max_uses_per_tenant) {
                return false;
            }
        }

        return true;
    }

    public function isApplicableToPlan(int $planId): bool
    {
        if (empty($this->applicable_plans)) {
            return true;
        }

        return in_array($planId, $this->applicable_plans);
    }

    public function isApplicableToBillingCycle(string $cycle): bool
    {
        if (empty($this->applicable_billing_cycles)) {
            return true;
        }

        return in_array($cycle, $this->applicable_billing_cycles);
    }

    public function calculateDiscount(float $amount): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $amount * ($this->discount_value / 100)
            : $this->discount_value;

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return round($discount, 2);
    }

    public function incrementUses(): void
    {
        $this->increment('current_uses');
    }

    public function getDiscountLabel(): string
    {
        if ($this->discount_type === 'percentage') {
            return "{$this->discount_value}%";
        }

        return '$' . number_format($this->discount_value, 2);
    }

    public static function generateCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function findByCode(string $code): ?static
    {
        return static::byCode($code)->first();
    }
}
