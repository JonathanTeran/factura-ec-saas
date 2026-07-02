<?php

namespace App\Models\Tenant;

use App\Enums\ExpenseCategory;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivedDocument extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'document_type',
        'access_key',
        'authorization_number',
        'authorization_date',
        'issuer_ruc',
        'issuer_name',
        'issue_date',
        'subtotal_0',
        'subtotal_5',
        'subtotal_12',
        'subtotal_15',
        'subtotal_no_tax',
        'total_discount',
        'total_tax',
        'total',
        'expense_category',
        'is_processed',
        'xml_path',
        'notes',
        'tags',
        'created_by',
    ];

    protected $casts = [
        'expense_category'  => ExpenseCategory::class,
        'issue_date'        => 'date',
        'authorization_date' => 'date',
        'subtotal_0'        => 'decimal:2',
        'subtotal_5'        => 'decimal:2',
        'subtotal_12'       => 'decimal:2',
        'subtotal_15'       => 'decimal:2',
        'subtotal_no_tax'   => 'decimal:2',
        'total_discount'    => 'decimal:2',
        'total_tax'         => 'decimal:2',
        'total'             => 'decimal:2',
        'is_processed'      => 'boolean',
        'tags'              => 'array',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeByCategory($query, ExpenseCategory $category)
    {
        return $query->where('expense_category', $category->value);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    // ==================== HELPERS ====================

    public function getSubtotal(): float
    {
        return (float) ($this->subtotal_0 + $this->subtotal_5 + $this->subtotal_12 + $this->subtotal_15 + $this->subtotal_no_tax);
    }

    public function isAuthorized(): bool
    {
        return $this->authorization_number !== null;
    }

    public function documentTypeLabel(): string
    {
        return match($this->document_type) {
            '01' => 'Factura',
            '03' => 'Liquidación de compra',
            '04' => 'Nota de crédito',
            '05' => 'Nota de débito',
            '06' => 'Guía de remisión',
            '07' => 'Retención',
            default => 'Documento',
        };
    }
}
