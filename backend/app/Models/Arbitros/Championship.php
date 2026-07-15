<?php

namespace App\Models\Arbitros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campeonato. Catálogo compartido: tenant_id NULL = oficial (visible para
 * todos); con tenant_id = personal de ese árbitro. Ver docs/arbitros-vertical-spec.md §3.1.
 */
class Championship extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'season',
        'external_ref',
        'invoice_window_start_day',
        'invoice_window_end_day',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'invoice_window_start_day' => 'integer',
        'invoice_window_end_day' => 'integer',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Tenant::class);
    }

    public function isPersonal(): bool
    {
        return $this->tenant_id !== null;
    }

    /** Oficiales activos (para todos) + los personales del árbitro dado. */
    public function scopeVisibleTo(Builder $query, int $tenantId): Builder
    {
        return $query->where(function (Builder $q) use ($tenantId) {
            $q->where(fn (Builder $g) => $g->whereNull('tenant_id')->where('is_active', true))
                ->orWhere('tenant_id', $tenantId);
        });
    }

    /** Ventana de recepción efectiva (override del campeonato o default de config). */
    public function windowStartDay(): int
    {
        return $this->invoice_window_start_day ?? (int) config('arbitros.invoice_window.start_day', 1);
    }

    public function windowEndDay(): int
    {
        return $this->invoice_window_end_day ?? (int) config('arbitros.invoice_window.end_day', 20);
    }
}
