<?php

namespace App\Models\Tenant;

use App\Models\SRI\ElectronicDocument;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'code',
        'name',
        'address',
        'city',
        'phone',
        'is_main',
        'is_active',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emissionPoints(): HasMany
    {
        return $this->hasMany(EmissionPoint::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== HELPERS ====================

    public function getFormattedCode(): string
    {
        return str_pad($this->code, 3, '0', STR_PAD_LEFT);
    }
}
