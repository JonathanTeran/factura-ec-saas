<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Tenant\Tenant;

class CustomerPortalToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'email',
        'identification',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function generateFor(int $tenantId, string $email, string $identification): self
    {
        // Invalidar tokens previos no usados para este email+tenant
        static::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return static::create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'identification' => $identification,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(config('portal.token_expiry_hours', 24)),
        ]);
    }

    public function isValid(): bool
    {
        return !$this->used_at && $this->expires_at->isFuture();
    }

    public function markUsed(string $ipAddress): void
    {
        $this->update([
            'used_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
