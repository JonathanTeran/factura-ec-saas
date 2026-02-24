<?php

namespace App\Models\Portal;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerPortalSession extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'email',
        'identification',
        'customer_name',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function createFromToken(
        CustomerPortalToken $token,
        string $customerName,
        string $ipAddress,
        string $userAgent,
    ): self {
        return static::create([
            'id' => Str::random(40),
            'tenant_id' => $token->tenant_id,
            'email' => $token->email,
            'identification' => $token->identification,
            'customer_name' => $customerName,
            'ip_address' => $ipAddress,
            'user_agent' => Str::limit($userAgent, 500),
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(config('portal.session_expiry_days', 7)),
        ]);
    }

    public function isValid(): bool
    {
        if ($this->expires_at->isPast()) {
            return false;
        }

        $inactivityLimit = config('portal.session_inactivity_minutes', 120);
        if ($this->last_activity_at->diffInMinutes(now()) > $inactivityLimit) {
            return false;
        }

        return true;
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
