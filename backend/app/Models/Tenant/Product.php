<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, BelongsToTenant, Searchable, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'main_code',
        'aux_code',
        'name',
        'description',
        'unit_price',
        'cost_price',
        'tax_code',
        'tax_percentage_code',
        'tax_rate',
        'has_ice',
        'ice_code',
        'track_inventory',
        'current_stock',
        'min_stock',
        'unit_of_measure',
        'type',
        'is_active',
        'is_favorite',
        'barcode',
        'image_path',
        'prices',
        'total_sold',
        'times_sold',
    ];

    protected $casts = [
        'unit_price' => 'decimal:6',
        'cost_price' => 'decimal:6',
        'tax_rate' => 'decimal:2',
        'has_ice' => 'boolean',
        'track_inventory' => 'boolean',
        'current_stock' => 'decimal:4',
        'min_stock' => 'decimal:4',
        'is_active' => 'boolean',
        'is_favorite' => 'boolean',
        'prices' => 'array',
        'total_sold' => 'decimal:2',
    ];

    // ==================== RELACIONES ====================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function documentItems(): HasMany
    {
        return $this->hasMany(\App\Models\SRI\DocumentItem::class);
    }

    // ==================== SCOUT ====================

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'main_code' => $this->main_code,
            'aux_code' => $this->aux_code,
            'description' => $this->description,
            'barcode' => $this->barcode,
        ];
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
                     ->whereColumn('current_stock', '<=', 'min_stock');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('main_code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    // ==================== IMPUESTOS ====================

    public function getTaxPercentage(): float
    {
        return match ($this->tax_percentage_code) {
            '0' => 0.00,
            '5' => 5.00,
            '2' => 12.00,
            '3' => 14.00,
            '4' => 15.00,
            '6' => 0.00, // No objeto de IVA
            '7' => 0.00, // Exento
            default => (float) $this->tax_rate,
        };
    }

    public function calculateTax(float $subtotal): float
    {
        return round($subtotal * ($this->getTaxPercentage() / 100), 2);
    }

    public function getPriceWithTax(): float
    {
        return round($this->unit_price * (1 + $this->getTaxPercentage() / 100), 2);
    }

    // ==================== INVENTARIO ====================

    public function adjustStock(float $quantity, string $type, ?int $referenceId = null, ?string $notes = null): void
    {
        if (!$this->track_inventory) {
            return;
        }

        $stockBefore = $this->current_stock;
        $this->current_stock += $quantity;
        $this->save();

        $this->inventoryMovements()->create([
            'tenant_id' => $this->tenant_id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $this->current_stock,
            'reference_type' => $referenceId ? 'electronic_document' : null,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->current_stock <= $this->min_stock;
    }

    // ==================== HELPERS ====================

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function updateSalesStats(float $amount): void
    {
        $this->increment('total_sold', $amount);
        $this->increment('times_sold');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useFallbackUrl('/images/product-placeholder.png');
    }
}
