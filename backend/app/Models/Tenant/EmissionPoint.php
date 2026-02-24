<?php

namespace App\Models\Tenant;

use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\SequentialNumber;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmissionPoint extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->branch->company;
    }

    public function sequentialNumbers(): HasMany
    {
        return $this->hasMany(SequentialNumber::class);
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

    public function getSeriesCode(): string
    {
        return $this->branch->getFormattedCode() . $this->getFormattedCode();
    }

    public function getNextSequential(string $documentType): int
    {
        $sequential = $this->sequentialNumbers()
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->first();

        if (!$sequential) {
            $sequential = $this->sequentialNumbers()->create([
                'tenant_id' => $this->tenant_id,
                'document_type' => $documentType,
                'current_number' => 0,
            ]);
        }

        $sequential->increment('current_number');

        return $sequential->current_number;
    }
}
