<?php

namespace App\Models\Tenant;

use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringInvoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'emission_point_id',
        'customer_id',
        'created_by',
        'frequency',
        'start_date',
        'end_date',
        'next_issue_date',
        'status',
        'items',
        'payment_methods',
        'additional_info',
        'notes',
        'currency',
        'total_issued',
        'max_issues',
        'last_issued_at',
        'notify_before_issue',
        'notify_days_before',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_issue_date' => 'date',
        'items' => 'array',
        'payment_methods' => 'array',
        'additional_info' => 'array',
        'last_issued_at' => 'datetime',
        'notify_before_issue' => 'boolean',
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

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'recurring_invoice_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueToday($query)
    {
        return $query->active()
            ->where('next_issue_date', '<=', now()->toDateString());
    }

    public function scopeDueSoon($query, int $days = 1)
    {
        return $query->active()
            ->where('next_issue_date', '<=', now()->addDays($days)->toDateString());
    }

    // ==================== HELPERS ====================

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
            'bimonthly' => 'Bimestral',
            'quarterly' => 'Trimestral',
            'semiannual' => 'Semestral',
            'annual' => 'Anual',
            default => $this->frequency,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Activa',
            'paused' => 'Pausada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function calculateNextIssueDate(): ?string
    {
        $current = $this->next_issue_date->copy();

        $next = match ($this->frequency) {
            'weekly' => $current->addWeek(),
            'biweekly' => $current->addWeeks(2),
            'monthly' => $current->addMonth(),
            'bimonthly' => $current->addMonths(2),
            'quarterly' => $current->addMonths(3),
            'semiannual' => $current->addMonths(6),
            'annual' => $current->addYear(),
        };

        // Check if past end date
        if ($this->end_date && $next->greaterThan($this->end_date)) {
            return null;
        }

        return $next->toDateString();
    }

    public function advanceToNextIssue(): void
    {
        $nextDate = $this->calculateNextIssueDate();

        $this->increment('total_issued');
        $this->update([
            'last_issued_at' => now(),
            'next_issue_date' => $nextDate,
            'status' => $nextDate ? 'active' : 'completed',
        ]);

        // Check max issues
        if ($this->max_issues && $this->total_issued >= $this->max_issues) {
            $this->update(['status' => 'completed']);
        }
    }

    public function isDue(): bool
    {
        return $this->status === 'active'
            && $this->next_issue_date->lte(now());
    }

    public function canIssue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->max_issues && $this->total_issued >= $this->max_issues) {
            return false;
        }

        if ($this->end_date && now()->greaterThan($this->end_date)) {
            return false;
        }

        return true;
    }

    public function getEstimatedTotal(): float
    {
        return collect($this->items)->sum(function ($item) {
            $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0) - ($item['discount'] ?? 0);
            $taxValue = round($subtotal * (($item['tax_rate'] ?? 0) / 100), 2);

            return $subtotal + $taxValue;
        });
    }
}
