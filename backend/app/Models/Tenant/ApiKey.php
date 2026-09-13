<?php

namespace App\Models\Tenant;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Llave de acceso a la API de integración (/api/v1/ext).
 *
 * Solo se guarda el hash sha256; la llave en claro (`fec_` + 40 chars) se
 * muestra una única vez al crearla o rotarla.
 */
class ApiKey extends Model
{
    use BelongsToTenant, HasFactory;

    /** Alcances disponibles (scope => etiqueta para la UI). */
    public const SCOPES = [
        'documents:read' => 'Consultar documentos (listar, ver, estado, RIDE, XML)',
        'documents:write' => 'Emitir documentos (crear, enviar, anular, reenviar correo)',
        'customers:read' => 'Consultar clientes',
        'customers:write' => 'Crear y editar clientes',
        'products:read' => 'Consultar productos',
        'products:write' => 'Crear y editar productos',
        'catalogs:read' => 'Consultar catálogos del SRI',
    ];

    /** Alcances que toda llave tiene aunque no se le asignen. */
    public const IMPLICIT_SCOPES = ['catalogs:read'];

    public const MAX_ACTIVE_PER_TENANT = 10;

    /** Peticiones por minuto según el plan (slug => límite). */
    public const PLAN_RATE_LIMITS = [
        'enterprise' => 300,
        'profesional' => 120,
        'negocio' => 60,
    ];

    public const DEFAULT_RATE_LIMIT = 30;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'rate_limit_per_minute',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ==================== GENERACIÓN ====================

    public static function generatePlainKey(): string
    {
        return 'fec_'.Str::random(40);
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function prefixFrom(string $key): string
    {
        return substr($key, 0, 12);
    }

    /**
     * Busca la llave por su valor en claro SIN el scope de tenant: el
     * middleware corre antes de que exista un usuario autenticado. Devuelve
     * también llaves inactivas o caducadas para poder informar el motivo.
     */
    public static function findByPlainKey(string $key): ?self
    {
        return static::withoutGlobalScopes()
            ->where('key_hash', static::hashKey($key))
            ->first();
    }

    /** @deprecated usar findByPlainKey(); se mantiene por compatibilidad. */
    public static function findByKey(string $key): ?self
    {
        $apiKey = static::findByPlainKey($key);

        return $apiKey && $apiKey->isValid() ? $apiKey : null;
    }

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== ESTADO ====================

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    // ==================== ALCANCES ====================

    /** @return list<string> */
    public function scopes(): array
    {
        $scopes = array_values(array_unique(array_filter((array) ($this->permissions ?: []), 'is_string')));

        return $scopes === [] ? ['*'] : $scopes;
    }

    public function hasScope(string $scope): bool
    {
        if (in_array($scope, self::IMPLICIT_SCOPES, true)) {
            return true;
        }

        $scopes = $this->scopes();

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    /** @deprecated usar hasScope(). */
    public function hasPermission(string $permission): bool
    {
        return $this->hasScope($permission);
    }

    // ==================== LÍMITES ====================

    public static function rateLimitForPlanSlug(?string $slug): int
    {
        return self::PLAN_RATE_LIMITS[$slug] ?? self::DEFAULT_RATE_LIMIT;
    }

    public function planRateLimit(): int
    {
        return self::rateLimitForPlanSlug($this->tenant?->currentPlan?->slug);
    }

    /** Límite real por minuto: el menor entre el de la llave y el del plan. */
    public function effectiveRateLimit(): int
    {
        $own = (int) $this->rate_limit_per_minute;
        $own = $own > 0 ? $own : PHP_INT_MAX;

        return max(1, min($own, $this->planRateLimit()));
    }

    // ==================== USO ====================

    /**
     * Registra el último uso como máximo una vez por minuto (evita un UPDATE
     * por cada petición de una integración activa).
     */
    public function touchUsage(?string $ip = null): void
    {
        if ($this->last_used_at
            && $this->last_used_at->gt(now()->subMinute())
            && $this->last_used_ip === $ip) {
            return;
        }

        $this->forceFill(['last_used_at' => now(), 'last_used_ip' => $ip])->saveQuietly();
    }

    /** @deprecated usar touchUsage(). */
    public function recordUsage(): void
    {
        $this->touchUsage();
    }

    /**
     * Reemplaza la credencial por una nueva (la anterior deja de servir de
     * inmediato) y devuelve el valor en claro para mostrarlo una sola vez.
     */
    public function rotate(): string
    {
        $plain = static::generatePlainKey();

        $this->forceFill([
            'key_hash' => static::hashKey($plain),
            'key_prefix' => static::prefixFrom($plain),
            'is_active' => true,
        ])->save();

        return $plain;
    }

    /** @return array<string, string> */
    public static function availablePermissions(): array
    {
        return self::SCOPES;
    }
}
