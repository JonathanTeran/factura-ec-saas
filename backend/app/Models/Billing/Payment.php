<?php

namespace App\Models\Billing;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Payment extends Model
{
    use HasFactory;

    protected static ?string $reportingAmountColumn = null;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'transaction_id',
        'invoice_number',
        'status',
        'payment_method',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'gateway',
        'gateway_payment_id',
        'gateway_transaction_id',
        'gateway_response',
        'description',
        // Transfer approval fields
        'transfer_receipt_path',
        'transfer_reference',
        'approved_by',
        'approved_at',
        'admin_notes',
        // Status timestamps
        'paid_at',
        'failed_at',
        'failure_reason',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        // Billing info
        'billing_name',
        'billing_email',
        'billing_identification',
        'billing_address',
        'billing_phone',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->invoice_number) {
                $payment->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', PaymentStatus::REFUNDED);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    // ==================== HELPERS ====================

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    public function canRefund(): bool
    {
        return $this->isCompleted() &&
               !$this->isRefunded() &&
               $this->paid_at?->diffInDays(now()) <= 30;
    }

    public function markAsCompleted(string $gatewayPaymentId, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_response' => $gatewayResponse,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'gateway_response' => $gatewayResponse,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }

    public function refund(float $amount, string $reason): void
    {
        $this->update([
            'status' => $amount >= $this->total_amount
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED,
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        $lastPayment = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastPayment
            ? (int) substr($lastPayment->invoice_number, -6) + 1
            : 1;

        return "PAY-{$year}{$month}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public function getPaymentMethodLabel(): string
    {
        return $this->payment_method->label();
    }

    public static function reportingAmountColumn(): string
    {
        if (static::$reportingAmountColumn !== null) {
            return static::$reportingAmountColumn;
        }

        static::$reportingAmountColumn = Schema::hasColumn((new static())->getTable(), 'total_amount')
            ? 'total_amount'
            : 'amount';

        return static::$reportingAmountColumn;
    }

    public function getTotalAmountAttribute($value): mixed
    {
        return $value ?? ($this->attributes['amount'] ?? 0);
    }

    public function getTaxAmountAttribute($value): mixed
    {
        return $value ?? 0;
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }
}
