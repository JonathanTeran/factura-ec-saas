<?php

namespace App\Models\Arbitros;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Árbitro detectado en los partidos publicados por la FEF. Se recalcula en
 * cada sincronización (FefRefereeDirectory). `tenant_id` apunta a la cuenta
 * de Facturón cuyo nombre configurado coincide (misma normalización que el
 * auto-matching).
 */
class FefReferee extends Model
{
    public const ROLE_LABELS = [
        'center' => 'Central',
        'assistant_1' => 'Asistente 1',
        'assistant_2' => 'Asistente 2',
        'fourth' => 'Cuarto árbitro',
    ];

    protected $fillable = [
        'name',
        'normalized_name',
        'matches_count',
        'roles',
        'first_seen_at',
        'last_seen_at',
        'tenant_id',
    ];

    protected $casts = [
        'matches_count' => 'integer',
        'roles' => 'array',
        'first_seen_at' => 'date',
        'last_seen_at' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeWithAccount(Builder $query): Builder
    {
        return $query->whereNotNull('tenant_id');
    }

    public function scopeWithoutAccount(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /** "Central 12 · Asistente 1 3" para la tabla del panel. */
    public function rolesSummary(): string
    {
        $parts = [];

        foreach (self::ROLE_LABELS as $key => $label) {
            $n = (int) data_get($this->roles, $key, 0);
            if ($n > 0) {
                $parts[] = "{$label} {$n}";
            }
        }

        return implode(' · ', $parts);
    }
}
