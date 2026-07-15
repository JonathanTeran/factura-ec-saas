<?php

namespace App\Models\Arbitros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Club. `name` = nombre completo oficial usado en el concepto de la factura.
 * Catálogo compartido: tenant_id NULL = oficial (visible para todos); con
 * tenant_id = personal de ese árbitro. Ver docs/arbitros-vertical-spec.md §3.1.
 */
class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'short_name',
        'city',
        'category',
        'external_ref',
        'logo_path',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Tenant::class);
    }

    public function isPersonal(): bool
    {
        return $this->tenant_id !== null;
    }

    /** Oficiales (para todos) + los personales del árbitro dado. */
    public function scopeVisibleTo(Builder $query, int $tenantId): Builder
    {
        return $query->where(function (Builder $q) use ($tenantId) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
        });
    }
}
