<?php

namespace App\Models\Arbitros;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Una corrida de sincronización con la API pública FEF (catálogo + matching +
 * directorio de árbitros). Es lo que el super admin ve en "Sincronización FEF".
 */
class FefSyncRun extends Model
{
    public const TRIGGER_SCHEDULE = 'schedule';

    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_CLI = 'cli';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const TRIGGER_LABELS = [
        self::TRIGGER_SCHEDULE => 'Programada',
        self::TRIGGER_MANUAL => 'Manual (panel)',
        self::TRIGGER_CLI => 'Consola',
    ];

    public const STATUS_LABELS = [
        self::STATUS_RUNNING => 'En curso',
        self::STATUS_SUCCESS => 'Correcta',
        self::STATUS_PARTIAL => 'Con avisos',
        self::STATUS_FAILED => 'Fallida',
    ];

    protected $fillable = [
        'trigger',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'stats',
        'api_errors',
        'error_message',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'stats' => 'array',
        'api_errors' => 'array',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SUCCESS, self::STATUS_PARTIAL, self::STATUS_FAILED]);
    }

    public function scopeOk(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SUCCESS, self::STATUS_PARTIAL]);
    }

    /** Última corrida que terminó trayendo datos (correcta o con avisos). */
    public static function latestOk(): ?self
    {
        return static::query()->ok()->latest('started_at')->first();
    }

    public static function latest(): ?self
    {
        return static::query()->orderByDesc('started_at')->first();
    }

    /** Sin corrida OK dentro de la ventana configurada (arbitros.sync.stale_hours). */
    public static function isStale(?int $hours = null): bool
    {
        $hours ??= (int) config('arbitros.sync.stale_hours', 3);
        $last = static::latestOk();

        return $last === null || $last->started_at->lt(Carbon::now()->subHours($hours));
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function stat(string $key, int $default = 0): int
    {
        return (int) data_get($this->stats, $key, $default);
    }

    public function apiErrorCount(): int
    {
        return count($this->api_errors ?? []);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function triggerLabel(): string
    {
        return self::TRIGGER_LABELS[$this->trigger] ?? $this->trigger;
    }
}
