<?php

namespace App\Services\Landing;

use Illuminate\Support\Facades\Cache;

/**
 * Caché de la respuesta pública de la landing (planes + textos de precios).
 * Se invalida al guardar/eliminar un plan y al guardar los textos desde Filament.
 */
final class PublicLandingCache
{
    public const KEY = 'landing:public';

    public const TTL_SECONDS = 300;

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
