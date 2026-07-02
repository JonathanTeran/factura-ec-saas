<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'rate_limit_per_minute',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions'  => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_active'    => 'boolean',
    ];

    // ==================== GENERACIÓN ====================

    public static function generatePlainKey(): string
    {
        return 'fec_' . Str::random(40);
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function prefixFrom(string $key): string
    {
        return substr($key, 0, 12);
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('key_hash', static::hashKey($key))
            ->where('is_active', true)
            ->first();
    }

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ==================== HELPERS ====================

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // sin restricciones = acceso total
        }
        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    public function recordUsage(): void
    {
        $this->updateQuietly(['last_used_at' => now()]);
    }

    public static function availablePermissions(): array
    {
        return [
            'invoices:read'    => 'Leer facturas',
            'invoices:create'  => 'Crear facturas',
            'customers:read'   => 'Leer clientes',
            'customers:write'  => 'Crear/editar clientes',
            'products:read'    => 'Leer productos',
            'catalogs:read'    => 'Leer catálogos SRI',
        ];
    }
}
