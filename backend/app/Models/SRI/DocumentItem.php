<?php

namespace App\Models\SRI;

use App\Models\Tenant\Product;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'electronic_document_id',
        'product_id',
        'main_code',
        'aux_code',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'tax_code',
        'tax_percentage_code',
        'tax_rate',
        'tax_base',
        'tax_value',
        'ice_code',
        'ice_rate',
        'ice_value',
        'sort_order',
        'additional_details',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_base' => 'decimal:2',
        'tax_value' => 'decimal:2',
        'ice_rate' => 'decimal:2',
        'ice_value' => 'decimal:2',
        'additional_details' => 'array',
    ];

    // ==================== RELACIONES ====================

    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->calculateTotals();
        });

        static::updating(function ($item) {
            $item->calculateTotals();
        });
    }

    // ==================== HELPERS ====================

    public function calculateTotals(): void
    {
        $this->subtotal = ($this->quantity * $this->unit_price) - $this->discount;
        $this->tax_base = $this->subtotal;
        $this->tax_value = round($this->tax_base * ($this->tax_rate / 100), 2);

        if ($this->ice_rate) {
            $this->ice_value = round($this->subtotal * ($this->ice_rate / 100), 2);
        }
    }

    public function getTotal(): float
    {
        return $this->subtotal + $this->tax_value + ($this->ice_value ?? 0);
    }

    public function getTaxPercentageLabel(): string
    {
        return match ($this->tax_percentage_code) {
            '0' => '0%',
            '5' => '5%',
            '2' => '12%',
            '3' => '14%',
            '4' => '15%',
            '6' => 'No IVA',
            '7' => 'Exento',
            default => $this->tax_rate . '%',
        };
    }
}
