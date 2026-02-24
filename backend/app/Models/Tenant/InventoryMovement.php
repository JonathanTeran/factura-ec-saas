<?php

namespace App\Models\Tenant;

use App\Enums\MovementType;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_type' => MovementType::class,
        'quantity' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // ==================== RELACIONES ====================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== SCOPES ====================

    public function scopeIncoming($query)
    {
        return $query->whereIn('movement_type', [
            MovementType::PURCHASE,
            MovementType::RETURN,
            MovementType::ADJUSTMENT_IN,
            MovementType::TRANSFER_IN,
            MovementType::INITIAL,
        ]);
    }

    public function scopeOutgoing($query)
    {
        return $query->whereIn('movement_type', [
            MovementType::SALE,
            MovementType::ADJUSTMENT_OUT,
            MovementType::TRANSFER_OUT,
            MovementType::DAMAGE,
            MovementType::EXPIRED,
        ]);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByType($query, MovementType $type)
    {
        return $query->where('movement_type', $type);
    }

    // ==================== HELPERS ====================

    public function isIncoming(): bool
    {
        return in_array($this->movement_type, [
            MovementType::PURCHASE,
            MovementType::RETURN,
            MovementType::ADJUSTMENT_IN,
            MovementType::TRANSFER_IN,
            MovementType::INITIAL,
        ]);
    }

    public function isOutgoing(): bool
    {
        return !$this->isIncoming();
    }

    public function getAbsoluteQuantity(): float
    {
        return abs($this->quantity);
    }

    public function getMovementLabel(): string
    {
        return $this->movement_type->label();
    }

    public function getMovementIcon(): string
    {
        return $this->movement_type->icon();
    }

    public function getMovementColor(): string
    {
        return $this->isIncoming() ? 'green' : 'red';
    }

    /**
     * Registra un movimiento de entrada por compra.
     */
    public static function recordPurchase(
        int $productId,
        float $quantity,
        float $unitCost,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $notes = null
    ): static {
        $product = Product::find($productId);
        $stockBefore = $product->current_stock;
        $stockAfter = $stockBefore + $quantity;

        $product->update(['current_stock' => $stockAfter]);

        return static::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $productId,
            'movement_type' => MovementType::PURCHASE,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Registra un movimiento de salida por venta.
     */
    public static function recordSale(
        int $productId,
        float $quantity,
        ?int $documentId = null,
        ?string $notes = null
    ): static {
        $product = Product::find($productId);
        $stockBefore = $product->current_stock;
        $stockAfter = $stockBefore - $quantity;

        $product->update(['current_stock' => $stockAfter]);

        return static::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $productId,
            'movement_type' => MovementType::SALE,
            'quantity' => -$quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $documentId ? 'App\\Models\\SRI\\ElectronicDocument' : null,
            'reference_id' => $documentId,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Registra un ajuste de inventario.
     */
    public static function recordAdjustment(
        int $productId,
        float $newStock,
        string $reason
    ): static {
        $product = Product::find($productId);
        $stockBefore = $product->current_stock;
        $difference = $newStock - $stockBefore;

        $product->update(['current_stock' => $newStock]);

        return static::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $productId,
            'movement_type' => $difference >= 0
                ? MovementType::ADJUSTMENT_IN
                : MovementType::ADJUSTMENT_OUT,
            'quantity' => $difference,
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'notes' => $reason,
            'created_by' => auth()->id(),
        ]);
    }
}
