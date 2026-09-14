<?php

namespace App\Jobs\Arbitros;

use App\Models\Arbitros\FefSyncRun;
use App\Services\Arbitros\FefSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Sincroniza catálogo FEF + auto-matching de árbitros + directorio de árbitros,
 * dejando la corrida registrada en fef_sync_runs. Programado cada hora y
 * disparable desde el panel del super admin (trigger "manual").
 */
class SyncFefMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(
        public string $trigger = FefSyncRun::TRIGGER_SCHEDULE,
        public ?int $userId = null,
    ) {}

    public function middleware(): array
    {
        // expireAfter: si el worker muere a mitad de corrida (timeout, OOM,
        // reinicio) el lock no se libera; sin expiración quedaba tomado para
        // siempre y cada corrida horaria fallaba con MaxAttemptsExceeded.
        return [(new WithoutOverlapping('arbitros-sync'))
            ->releaseAfter(300)
            ->expireAfter($this->timeout * 2)];
    }

    public function handle(FefSyncService $sync): void
    {
        // Sin tenants árbitro no hay nada que sincronizar: evita llamadas a la
        // API de la FEF en instalaciones que no usan el vertical.
        if (! $sync->hasRefereeTenants()) {
            return;
        }

        $sync->run($this->trigger, $this->userId);
    }
}
