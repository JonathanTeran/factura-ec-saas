<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    public static array $availableEvents = [
        'document.authorized',
        'document.rejected',
        'document.created',
        'document.signed',
        'document.voided',
        'document.failed',
    ];

    public static function generateSecret(): string
    {
        return 'whsec_' . Str::random(40);
    }

    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'failure_count' => 0,
        ]);
    }

    public function recordFailure(): void
    {
        $this->increment('failure_count');
        $this->update(['last_triggered_at' => now()]);
    }

    public function isDisabledDueToFailures(): bool
    {
        return $this->failure_count >= 10;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('failure_count', '<', 10);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
