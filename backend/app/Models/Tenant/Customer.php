<?php

namespace App\Models\Tenant;

use App\Enums\IdentificationType;
use App\Models\SRI\ElectronicDocument;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, Searchable;

    protected $fillable = [
        'tenant_id',
        'identification_type',
        'identification',
        'business_name',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'is_active',
        'total_invoiced',
        'last_invoice_date',
        'notes',
        'tags',
    ];

    protected $casts = [
        'identification_type' => IdentificationType::class,
        'is_active' => 'boolean',
        'total_invoiced' => 'decimal:2',
        'last_invoice_date' => 'date',
        'tags' => 'array',
    ];

    // ==================== RELACIONES ====================

    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class);
    }

    public function documents(): HasMany
    {
        return $this->electronicDocuments();
    }

    public function invoices(): HasMany
    {
        return $this->electronicDocuments()->where('document_type', '01');
    }

    public function getBusinessNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setBusinessNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    // ==================== SCOUT ====================

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'identification' => $this->identification,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
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
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('identification', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    // ==================== HELPERS ====================

    public function isConsumidorFinal(): bool
    {
        return $this->identification_type === IdentificationType::CONSUMIDOR_FINAL;
    }

    public function getFormattedIdentification(): string
    {
        return $this->identification_type->label() . ': ' . $this->identification;
    }

    public function updateInvoiceStats(float $amount): void
    {
        $this->increment('total_invoiced', $amount);
        $this->update(['last_invoice_date' => now()]);
    }

    public static function consumidorFinal(int $tenantId): self
    {
        return static::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'identification_type' => IdentificationType::CONSUMIDOR_FINAL,
                'identification' => '9999999999999',
            ],
            [
                'name' => 'CONSUMIDOR FINAL',
                'is_active' => true,
            ]
        );
    }
}
