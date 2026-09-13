<?php

namespace App\Console\Commands;

use App\Models\Arbitros\FefSyncRun;
use App\Services\Arbitros\FefSyncService;
use Illuminate\Console\Command;

/**
 * Revisión diaria: si no hay una sincronización FEF correcta dentro de la
 * ventana (arbitros.sync.stale_hours), avisa a los super admins.
 */
class CheckFefSyncHealth extends Command
{
    protected $signature = 'arbitros:sync-health';

    protected $description = 'Avisa a los super admins si la sincronización FEF lleva demasiado tiempo sin una corrida correcta';

    public function handle(FefSyncService $sync): int
    {
        if (! $sync->hasRefereeTenants()) {
            $this->info('Sin cuentas de árbitro: nada que revisar.');

            return self::SUCCESS;
        }

        $healthy = $sync->checkHealth();
        $last = FefSyncRun::latestOk();

        if ($healthy) {
            $this->info('Sincronización FEF al día (última correcta: '.($last?->started_at?->diffForHumans() ?? 'n/a').').');

            return self::SUCCESS;
        }

        $this->warn('Sin sincronización FEF correcta en las últimas '.config('arbitros.sync.stale_hours', 3).' horas; super admins avisados.');

        return self::FAILURE;
    }
}
