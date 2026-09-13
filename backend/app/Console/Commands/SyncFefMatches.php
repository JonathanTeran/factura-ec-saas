<?php

namespace App\Console\Commands;

use App\Models\Arbitros\FefSyncRun;
use App\Services\Arbitros\FefIngestService;
use App\Services\Arbitros\FefSyncService;
use App\Services\Arbitros\RefereeMatcher;
use Illuminate\Console\Command;

/**
 * Sincroniza el catálogo FEF (campeonatos/clubes/partidos), corre el
 * auto-matching de árbitros y actualiza el directorio de árbitros. La corrida
 * queda registrada en fef_sync_runs (visible en el panel del super admin).
 * Con --dry-run no se escribe nada ni se registra corrida.
 */
class SyncFefMatches extends Command
{
    protected $signature = 'arbitros:sync-matches
        {--dry-run : No escribe nada; solo reporta lo que haría}
        {--tenant= : Limitar el matching a un tenant específico (solo con --dry-run)}
        {--since-days= : Ventana de partidos a considerar en el matching (solo con --dry-run)}';

    protected $description = 'Sincroniza partidos desde la API pública FEF, propone pendientes a los árbitros y registra la corrida';

    public function handle(FefSyncService $sync, FefIngestService $ingest, RefereeMatcher $matcher): int
    {
        if ($this->option('dry-run')) {
            return $this->dryRun($ingest, $matcher);
        }

        $this->info('Sincronizando catálogo FEF, matching y directorio de árbitros…');

        try {
            $run = $sync->run(FefSyncRun::TRIGGER_CLI);
        } catch (\Throwable $e) {
            $this->error('La sincronización falló: '.$e->getMessage());
            $this->line('La corrida quedó registrada como fallida en el panel (Árbitros → Sincronización FEF).');

            return self::FAILURE;
        }

        $this->table(
            ['Estado', 'Duración', 'Campeonatos', 'Clubes nuevos', 'Partidos nuevos', 'Partidos actualizados', 'Propuestas', 'Árbitros', 'Con cuenta', 'Errores API'],
            [[
                $run->statusLabel(),
                round(($run->duration_ms ?? 0) / 1000, 1).' s',
                $run->stat('championships'),
                $run->stat('clubs'),
                $run->stat('matches_created'),
                $run->stat('matches_updated'),
                $run->stat('proposals'),
                $run->stat('referees'),
                $run->stat('referees_linked'),
                $run->apiErrorCount(),
            ]]
        );

        foreach ($run->api_errors ?? [] as $error) {
            $this->warn('API FEF: '.($error['path'] ?? '?').' → '.($error['reason'] ?? '?'));
        }

        return $run->isFailed() ? self::FAILURE : self::SUCCESS;
    }

    private function dryRun(FefIngestService $ingest, RefereeMatcher $matcher): int
    {
        $this->info('[dry-run] Sincronizando catálogo FEF…');
        $ingestStats = $ingest->sync(true);

        $this->table(
            ['Campeonatos', 'Clubes nuevos', 'Partidos nuevos', 'Partidos actualizados', 'Inactivos (omitidos)'],
            [[
                $ingestStats['championships'],
                $ingestStats['clubs'],
                $ingestStats['matches_created'],
                $ingestStats['matches_updated'],
                $ingestStats['skipped_inactive'],
            ]]
        );

        $this->info('[dry-run] Corriendo auto-matching de árbitros…');
        $matchStats = $matcher->run(
            tenantId: $this->option('tenant') ? (int) $this->option('tenant') : null,
            sinceDays: $this->option('since-days') ? (int) $this->option('since-days') : null,
            dryRun: true,
        );

        $this->table(
            ['Árbitros con nombre configurado', 'Propuestas detectadas'],
            [[$matchStats['tenants'], $matchStats['proposals']]]
        );

        return self::SUCCESS;
    }
}
