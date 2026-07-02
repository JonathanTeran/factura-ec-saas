<?php

namespace App\Models\Tenant;

use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobro registrado sobre un comprobante emitido (factura, nota de débito…).
 */
class DocumentPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'electronic_document_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
