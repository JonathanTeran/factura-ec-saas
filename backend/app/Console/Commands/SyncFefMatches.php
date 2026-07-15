<?php

namespace App\Console\Commands;

use App\Services\Arbitros\FefIngestService;
use App\Services\Arbitros\RefereeMatcher;
use Illuminate\Console\Command;

/**
 * Sincroniza el catálogo FEF (campeonatos/clubes/partidos) y corre el
 * auto-matching de árbitros. Ver docs/arbitros-vertical-spec.md §6.
 */
class SyncFefMatches extends Command
{
    protected $signature = 'arbitros:sync-matches
        {--dry-run : No escribe nada; solo reporta lo que haría}
        {--tenant= : Limitar el matching a un tenant específico}
        {--since-days= : Ventana de partidos a considerar en el matching}';

    protected $description = 'Sincroniza partidos desde la API pública FEF y propone pendientes por facturar a los árbitros';

    public function handle(FefIngestService $ingest, RefereeMatcher $matcher): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '') . 'Sincronizando catálogo FEF…');
        $ingestStats = $ingest->sync($dryRun);

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

        $this->info('Corriendo auto-matching de árbitros…');
        $matchStats = $matcher->run(
            tenantId: $this->option('tenant') ? (int) $this->option('tenant') : null,
            sinceDays: $this->option('since-days') ? (int) $this->option('since-days') : null,
            dryRun: $dryRun,
        );

        $this->table(
            ['Árbitros con nombre configurado', 'Propuestas ' . ($dryRun ? 'detectadas' : 'creadas')],
            [[$matchStats['tenants'], $matchStats['proposals']]]
        );

        return self::SUCCESS;
    }
}
