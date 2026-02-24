<?php

namespace App\Models\Accounting;

use App\Models\Tenant\Company;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMappingTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'document_type',
        'name',
        'mapping_rules',
        'is_active',
    ];

    protected $casts = [
        'mapping_rules' => 'array',
        'is_active' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDocumentType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
