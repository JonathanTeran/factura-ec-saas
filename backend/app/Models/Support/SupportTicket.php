<?php

namespace App\Models\Support;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use App\Models\Tenant\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to',
        'subject',
        'category',
        'priority',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'status'      => TicketStatus::class,
        'priority'    => TicketPriority::class,
        'category'    => TicketCategory::class,
        'resolved_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', TicketStatus::OPEN);
    }

    public function scopeByStatus($query, TicketStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==================== HELPERS ====================

    public function isOpen(): bool
    {
        return in_array($this->status, [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_CUSTOMER]);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED]);
    }

    public function resolve(): void
    {
        $this->update([
            'status'      => TicketStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
    }
}
