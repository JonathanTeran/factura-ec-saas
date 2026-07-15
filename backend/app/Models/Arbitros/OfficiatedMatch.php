<?php

namespace App\Models\Arbitros;

use App\Models\SRI\ElectronicDocument;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partido pitado por un árbitro = unidad "pendiente por facturar" (POR TENANT).
 * Ver docs/arbitros-vertical-spec.md §3.2 y §4.
 */
class OfficiatedMatch extends Model
{
    use HasFactory, BelongsToTenant;

    /** Estados del ciclo de facturación. */
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_BLOCKED_WINDOW = 'blocked_window';

    /** Roles arbitrales. Terna + cuarto vienen de la API; el resto es manual. */
    public const ROLE_ARBITRO = 'arbitro';
    public const ROLE_ASISTENTE_1 = 'asistente_1';
    public const ROLE_ASISTENTE_2 = 'asistente_2';
    public const ROLE_CUARTO = 'cuarto';
    public const ROLE_VAR = 'var';
    public const ROLE_COMISARIO = 'comisario';
    public const ROLE_DELEGADO = 'delegado';

    protected $fillable = [
        'tenant_id',
        'football_match_id',
        'championship_id',
        'home_club_id',
        'away_club_id',
        'match_date',
        'role',
        'fee',
        'status',
        'electronic_document_id',
        'invoiced_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'match_date' => 'date',
        'fee' => 'decimal:2',
        'invoiced_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'football_match_id');
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    public function awayClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
