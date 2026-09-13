<?php

namespace App\Models\Tenant;

use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Services\Document\DocumentTotals;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class RecurringInvoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /** Frecuencias soportadas (mismo enum que la columna en la BD). */
    public const FREQUENCIES = [
        'weekly',
        'biweekly',
        'monthly',
        'bimonthly',
        'quarterly',
        'semiannual',
        'annual',
    ];

    public const STATUSES = ['active', 'paused', 'completed', 'cancelled'];

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'emission_point_id',
        'customer_id',
        'created_by',
        'name',
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
        'auto_send',
        'last_error',
        'last_error_at',
        'reminder_sent_for',
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
        'notify_days_before' => 'integer',
        'auto_send' => 'boolean',
        'last_error_at' => 'datetime',
        'reminder_sent_for' => 'date',
        'total_issued' => 'integer',
        'max_issues' => 'integer',
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
            ->whereDate('next_issue_date', '<=', now()->toDateString());
    }

    public function scopeDueSoon($query, int $days = 1)
    {
        return $query->active()
            ->whereDate('next_issue_date', '<=', now()->addDays($days)->toDateString());
    }

    // ==================== HELPERS ====================

    public static function frequencyLabelFor(?string $frequency): string
    {
        return match ($frequency) {
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
            'bimonthly' => 'Bimestral',
            'quarterly' => 'Trimestral',
            'semiannual' => 'Semestral',
            'annual' => 'Anual',
            default => (string) $frequency,
        };
    }

    public function frequencyLabel(): string
    {
        return self::frequencyLabelFor($this->frequency);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Activa',
            'paused' => 'Pausada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => (string) $this->status,
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

    /**
     * Siguiente fecha de emisión a partir de la actual según la frecuencia.
     * Devuelve null cuando la siguiente supera end_date (la recurrente se
     * completa).
     *
     * @throws InvalidArgumentException frecuencia desconocida (antes era un
     *                                  UnhandledMatchError que escapaba al catch del lote).
     */
    public function calculateNextIssueDate(): ?string
    {
        $current = ($this->next_issue_date ?? now())->copy();

        $next = match ($this->frequency) {
            'weekly' => $current->addWeek(),
            'biweekly' => $current->addWeeks(2),
            'monthly' => $current->addMonthNoOverflow(),
            'bimonthly' => $current->addMonthsNoOverflow(2),
            'quarterly' => $current->addMonthsNoOverflow(3),
            'semiannual' => $current->addMonthsNoOverflow(6),
            'annual' => $current->addYearNoOverflow(),
            default => throw new InvalidArgumentException(
                "Frecuencia no soportada: {$this->frequency}. Usa una de: ".implode(', ', self::FREQUENCIES).'.'
            ),
        };

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
            && $this->next_issue_date
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

        if ($this->end_date && now()->startOfDay()->greaterThan($this->end_date)) {
            return false;
        }

        return true;
    }

    /** Fecha en la que corresponde avisar de la próxima emisión (o null). */
    public function reminderDate(): ?\Carbon\CarbonInterface
    {
        if (! $this->notify_before_issue || ! $this->next_issue_date || (int) $this->notify_days_before <= 0) {
            return null;
        }

        return $this->next_issue_date->copy()->subDays((int) $this->notify_days_before);
    }

    public function getEstimatedTotal(): float
    {
        return DocumentTotals::fromItems($this->items ?? [])['totals']['total'];
    }
}
