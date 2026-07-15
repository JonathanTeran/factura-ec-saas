<?php

namespace App\Models\Arbitros;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partido (catálogo público global). Nombre de clase `FootballMatch` porque
 * `Match` es palabra reservada en PHP 8. Tabla `football_matches`.
 * Ver docs/arbitros-vertical-spec.md §3.1 y §6.
 */
class FootballMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'championship_id',
        'home_club_id',
        'away_club_id',
        'match_date',
        'stage',
        'external_ref',
        'officials',
        'source',
        'published_at',
    ];

    protected $casts = [
        'match_date' => 'date',
        'officials' => 'array',
        'published_at' => 'datetime',
    ];

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
}
