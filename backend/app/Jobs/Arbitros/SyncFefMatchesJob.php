<?php

namespace App\Jobs\Arbitros;

use App\Models\Tenant\Tenant;
use App\Services\Arbitros\FefIngestService;
use App\Services\Arbitros\RefereeMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza catálogo FEF + auto-matching de árbitros. Programado cada hora y
 * disparable manualmente desde el panel del super admin (Filament).
 */
class SyncFefMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;
    public int $tries = 2;

    public function middleware(): array
    {
        return [(new WithoutOverlapping('arbitros-sync'))->releaseAfter(300)];
    }

    public function handle(FefIngestService $ingest, RefereeMatcher $matcher): void
    {
        // Sin tenants árbitro no hay nada que sincronizar: evita llamadas a la
        // API de la FEF en instalaciones que no usan el vertical.
        $hasReferees = Tenant::where('business_type', Tenant::BUSINESS_TYPE_REFEREE)->exists();

        if (! $hasReferees) {
            return;
        }

        $ingestStats = $ingest->sync();
        $matchStats = $matcher->run();

        Log::info('[arbitros] Sync FEF completado', $ingestStats + $matchStats);
    }
}
