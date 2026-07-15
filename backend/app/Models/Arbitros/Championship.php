<?php

namespace App\Models\Arbitros;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campeonato (catálogo público global). Ver docs/arbitros-vertical-spec.md §3.1.
 */
class Championship extends Model
{
    use HasFactory;

    protected $fillable = [
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
