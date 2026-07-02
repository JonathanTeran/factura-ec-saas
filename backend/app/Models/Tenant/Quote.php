<?php

namespace App\Models\Tenant;

use App\Enums\QuoteStatus;
use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'customer_id',
        'created_by',
        'quote_number',
        'status',
        'issue_date',
        'expiry_date',
        'subtotal',
        'total_discount',
        'total_tax',
        'total',
        'notes',
        'payment_terms',
        'converted_to_document_id',
    ];

    protected $casts = [
        'status'      => QuoteStatus::class,
        'issue_date'  => 'date',
        'expiry_date' => 'date',
        'subtotal'    => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax'   => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function convertedDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'converted_to_document_id');
    }

    // ==================== SCOPES ====================

    public function scopeByStatus($query, QuoteStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [QuoteStatus::DRAFT, QuoteStatus::SENT, QuoteStatus::ACCEPTED]);
    }

    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->whereIn('status', [QuoteStatus::DRAFT, QuoteStatus::SENT])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    // ==================== HELPERS ====================

    public function isDraft(): bool
    {
        return $this->status === QuoteStatus::DRAFT;
    }

    public function isAccepted(): bool
    {
        return $this->status === QuoteStatus::ACCEPTED;
    }

    public function isExpired(): bool
    {
        return $this->status === QuoteStatus::EXPIRED
            || ($this->expiry_date && $this->expiry_date->isPast() && !in_array($this->status, [QuoteStatus::INVOICED, QuoteStatus::REJECTED]));
    }

    public function canBeConverted(): bool
    {
        return $this->status === QuoteStatus::ACCEPTED && $this->converted_to_document_id === null;
    }
}
