<?php

namespace App\Models\Arbitros;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Club (catálogo público global). `name` = nombre completo oficial usado en el
 * concepto de la factura. Ver docs/arbitros-vertical-spec.md §3.1.
 */
class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'city',
        'category',
        'external_ref',
        'logo_path',
    ];
}
