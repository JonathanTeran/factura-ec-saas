<?php

namespace App\Models\Tenant;

use App\Enums\PurchaseStatus;
use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'supplier_id',
        'document_type',
        'supplier_document_number',
        'supplier_authorization',
        'issue_date',
        'authorization_date',
        'subtotal_0',
        'subtotal_5',
        'subtotal_12',
        'subtotal_15',
        'subtotal_no_tax',
        'total_discount',
        'total_tax',
        'total',
        'status',
        'withholding_document_id',
        'payment_methods',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'issue_date' => 'date',
        'authorization_date' => 'date',
        'subtotal_0' => 'decimal:2',
        'subtotal_5' => 'decimal:2',
        'subtotal_12' => 'decimal:2',
        'subtotal_15' => 'decimal:2',
        'subtotal_no_tax' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_methods' => 'array',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function withholdingDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'withholding_document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, PurchaseStatus $status)
    {
        return $query->where('status', $status);
    }

    // ==================== HELPERS ====================

    public function getSubtotal(): float
    {
        return $this->subtotal_no_tax + $this->subtotal_0 + $this->subtotal_5
             + $this->subtotal_12 + $this->subtotal_15;
    }

    public function calculateTotals(): void
    {
        $subtotal0 = 0;
        $subtotal5 = 0;
        $subtotal12 = 0;
        $subtotal15 = 0;
        $subtotalNoTax = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($this->items as $item) {
            $totalDiscount += $item->discount;

            match ($item->tax_percentage_code) {
                '0' => $subtotal0 += $item->subtotal,
                '5' => $subtotal5 += $item->subtotal,
                '2' => $subtotal12 += $item->subtotal,
                '3', '4' => $subtotal15 += $item->subtotal,
                default => $subtotalNoTax += $item->subtotal,
            };

            $totalTax += $item->tax_value;
        }

        $this->update([
            'subtotal_0' => $subtotal0,
            'subtotal_5' => $subtotal5,
            'subtotal_12' => $subtotal12,
            'subtotal_15' => $subtotal15,
            'subtotal_no_tax' => $subtotalNoTax,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total' => $subtotal0 + $subtotal5 + $subtotal12 + $subtotal15 + $subtotalNoTax + $totalTax,
        ]);
    }

    public function hasWithholding(): bool
    {
        return $this->withholding_document_id !== null;
    }
}
