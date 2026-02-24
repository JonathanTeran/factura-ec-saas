<?php

namespace App\Models\Tenant;

use App\Enums\IdentificationType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'identification_type',
        'identification',
        'business_name',
        'commercial_name',
        'email',
        'phone',
        'address',
        'city',
        'is_active',
        'is_withholding_agent',
        'accounting_account',
        'notes',
        'total_purchased',
        'last_purchase_date',
    ];

    protected $casts = [
        'identification_type' => IdentificationType::class,
        'is_active' => 'boolean',
        'is_withholding_agent' => 'boolean',
        'total_purchased' => 'decimal:2',
        'last_purchase_date' => 'date',
    ];

    // ==================== RELACIONES ====================

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('business_name', 'like', "%{$term}%")
              ->orWhere('commercial_name', 'like', "%{$term}%")
              ->orWhere('identification', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    // ==================== HELPERS ====================

    public function getDisplayNameAttribute(): string
    {
        return $this->commercial_name ?: $this->business_name;
    }

    public function getFormattedIdentification(): string
    {
        return $this->identification_type->label() . ': ' . $this->identification;
    }

    public function updatePurchaseStats(float $amount): void
    {
        $this->increment('total_purchased', $amount);
        $this->update(['last_purchase_date' => now()]);
    }
}
