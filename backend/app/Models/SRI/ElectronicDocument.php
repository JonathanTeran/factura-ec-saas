<?php

namespace App\Models\SRI;

use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use App\Models\User;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\Company;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Customer;
use App\Models\Tenant\RecurringInvoice;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectronicDocument extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'emission_point_id',
        'customer_id',
        'created_by',
        'document_type',
        'environment',
        'series',
        'sequential',
        'access_key',
        'status',
        'authorization_number',
        'authorization_date',
        'subtotal_no_tax',
        'subtotal_0',
        'subtotal_5',
        'subtotal_12',
        'subtotal_15',
        'total_discount',
        'total_tax',
        'total_ice',
        'tip',
        'total',
        'xml_unsigned_path',
        'xml_signed_path',
        'xml_authorized_path',
        'ride_pdf_path',
        'sri_response',
        'sri_errors',
        'sri_attempts',
        'last_sri_attempt_at',
        'related_document_id',
        'related_document_type',
        'related_document_number',
        'related_document_date',
        'email_sent',
        'email_sent_at',
        'whatsapp_sent',
        'whatsapp_sent_at',
        'payment_methods',
        'additional_info',
        'issue_date',
        'due_date',
        'currency',
        'notes',
        'recurring_invoice_id',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'authorization_date' => 'datetime',
        'subtotal_no_tax' => 'decimal:2',
        'subtotal_0' => 'decimal:2',
        'subtotal_5' => 'decimal:2',
        'subtotal_12' => 'decimal:2',
        'subtotal_15' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_ice' => 'decimal:2',
        'tip' => 'decimal:2',
        'total' => 'decimal:2',
        'sri_response' => 'array',
        'sri_errors' => 'array',
        'last_sri_attempt_at' => 'datetime',
        'related_document_date' => 'date',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'whatsapp_sent' => 'boolean',
        'whatsapp_sent_at' => 'datetime',
        'payment_methods' => 'array',
        'additional_info' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function emissionPoint(): BelongsTo
    {
        return $this->belongsTo(EmissionPoint::class);
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
        return $this->hasMany(DocumentItem::class, 'electronic_document_id');
    }

    public function withholdingDetails(): HasMany
    {
        return $this->hasMany(WithholdingDetail::class, 'electronic_document_id');
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'related_document_id');
    }

    public function relatedDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'related_document_id');
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    // ==================== SCOPES ====================

    public function scopeAuthorized($query)
    {
        return $query->where('status', DocumentStatus::AUTHORIZED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            DocumentStatus::DRAFT,
            DocumentStatus::PROCESSING,
            DocumentStatus::SIGNED,
            DocumentStatus::SENT,
        ]);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [
            DocumentStatus::REJECTED,
            DocumentStatus::FAILED,
        ]);
    }

    public function scopeByType($query, DocumentType $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeInvoices($query)
    {
        return $query->byType(DocumentType::FACTURA);
    }

    public function scopeCreditNotes($query)
    {
        return $query->byType(DocumentType::NOTA_CREDITO);
    }

    public function scopeRetentions($query)
    {
        return $query->byType(DocumentType::RETENCION);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    // ==================== ACCESSORS ====================

    public function getDocumentNumberAttribute(): string
    {
        return $this->getDocumentNumber();
    }

    // ==================== HELPERS ====================

    public function getDocumentNumber(): string
    {
        return sprintf(
            '%s-%s-%s',
            $this->branch->code ?? '000',
            $this->emissionPoint->code ?? '000',
            str_pad($this->sequential, 9, '0', STR_PAD_LEFT)
        );
    }

    public function getFullDocumentNumber(): string
    {
        return $this->document_type->shortLabel() . ' ' . $this->getDocumentNumber();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isAuthorized(): bool
    {
        return $this->status === DocumentStatus::AUTHORIZED;
    }

    public function canRetry(): bool
    {
        return in_array($this->status, [DocumentStatus::FAILED, DocumentStatus::REJECTED])
               && $this->sri_attempts < 3;
    }

    public function canVoid(): bool
    {
        return $this->isAuthorized() &&
               $this->document_type === DocumentType::FACTURA &&
               $this->relatedDocuments()->authorized()->doesntExist();
    }

    public function getSubtotal(): float
    {
        return $this->subtotal_no_tax + $this->subtotal_0 + $this->subtotal_5 + $this->subtotal_12 + $this->subtotal_15;
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
                '6', '7' => $subtotalNoTax += $item->subtotal,
                default => $subtotal15 += $item->subtotal,
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
            'total' => $subtotal0 + $subtotal5 + $subtotal12 + $subtotal15 + $subtotalNoTax + $totalTax + $this->tip,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => DocumentStatus::PROCESSING]);
    }

    public function markAsSent(): void
    {
        $this->update(['status' => DocumentStatus::SENT]);
    }

    public function markAsAuthorized(string $authNumber, string $authDate): void
    {
        $this->update([
            'status' => DocumentStatus::AUTHORIZED,
            'authorization_number' => $authNumber,
            'authorization_date' => $authDate,
        ]);
    }

    public function markAsRejected(array $errors): void
    {
        $this->update([
            'status' => DocumentStatus::REJECTED,
            'sri_errors' => $errors,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('sri_attempts');
        $this->update(['last_sri_attempt_at' => now()]);
    }
}
