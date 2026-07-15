<?php

namespace App\Services\Arbitros;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\FootballMatch;
use Illuminate\Support\Carbon;

/**
 * Ingesta del catálogo público (campeonatos, clubes, partidos) desde la API FEF.
 * Idempotente: upsert por external_ref / nombre oficial. El super admin puede
 * desactivar un campeonato (is_active=false) para excluirlo de la sincronización.
 * Ver docs/arbitros-vertical-spec.md §6.
 */
class FefIngestService
{
    public function __construct(private FefApiClient $api)
    {
    }

    /**
     * Sincroniza campeonatos + partidos. Devuelve estadísticas.
     *
     * @return array{championships: int, clubs: int, matches_created: int, matches_updated: int, skipped_inactive: int}
     */
    public function sync(bool $dryRun = false): array
    {
        $stats = [
            'championships' => 0,
            'clubs' => 0,
            'matches_created' => 0,
            'matches_updated' => 0,
            'skipped_inactive' => 0,
        ];

        $competitions = $this->api->competitions();

        if ($competitions === []) {
            // Fallback: feed global sin filtro (agrupa por 'tournament').
            $this->syncMatches($this->api->recentMatches(), null, $dryRun, $stats);

            return $stats;
        }

        foreach ($competitions as $comp) {
            $hierarchyId = (string) ($comp['hierarchy_id'] ?? '');
            $name = trim((string) ($comp['competition_name'] ?? ''));

            if ($hierarchyId === '' || $name === '') {
                continue;
            }

            if ($dryRun) {
                $championship = Championship::where('external_ref', $hierarchyId)->first();
            } else {
                // firstOrNew + save (y no updateOrCreate) para NO pisar is_active:
                // ese flag lo controla el super admin. En filas nuevas se setea
                // explícitamente porque el default de BD no llega al modelo en memoria.
                $championship = Championship::firstOrNew(['external_ref' => $hierarchyId]);
                $championship->fill([
                    'name' => $name,
                    'season' => $this->seasonFromName($name),
                    'category' => $this->categoryFromName($name),
                ]);
                if (! $championship->exists) {
                    $championship->is_active = true;
                }
                $championship->save();
            }

            if ($championship) {
                $stats['championships']++;

                if (! $championship->is_active) {
                    $stats['skipped_inactive']++;
                    continue; // el super admin lo excluyó de la sincronización
                }
            }

            $matches = $this->api->recentMatches($hierarchyId);
            $this->syncMatches($matches, $championship, $dryRun, $stats);
        }

        return $stats;
    }

    /** Upsert de clubes y partidos de una lista del feed. */
    private function syncMatches(array $matches, ?Championship $championship, bool $dryRun, array &$stats): void
    {
        $clubCache = [];

        foreach ($matches as $m) {
            $externalRef = (string) ($m['match_id'] ?? '');
            $date = (string) ($m['match_date'] ?? '');
            $home = trim((string) ($m['home_team'] ?? ''));
            $away = trim((string) ($m['away_team'] ?? ''));

            if ($externalRef === '' || $date === '' || $home === '' || $away === '') {
                continue;
            }

            // Cada partido SIEMPRE tiene campeonato (§5.5). En el feed global se
            // resuelve por nombre de torneo.
            $ch = $championship ?? $this->championshipByName((string) ($m['tournament'] ?? ''), $dryRun);
            if (! $ch) {
                continue; // cuarentena: sin campeonato identificable no se ingesta
            }

            if ($dryRun) {
                $stats[FootballMatch::where('external_ref', $externalRef)->exists() ? 'matches_updated' : 'matches_created']++;
                continue;
            }

            $homeClub = $this->club($home, $ch, $clubCache, $stats);
            $awayClub = $this->club($away, $ch, $clubCache, $stats);

            $officials = $this->officialsFrom($m);

            $match = FootballMatch::updateOrCreate(
                ['external_ref' => $externalRef],
                [
                    'championship_id' => $ch->id,
                    'home_club_id' => $homeClub->id,
                    'away_club_id' => $awayClub->id,
                    'match_date' => $date,
                    'stage' => $m['stage'] ?? null,
                    'officials' => $officials,
                    'source' => 'scraper',
                ]
            );

            if ($match->wasRecentlyCreated) {
                $stats['matches_created']++;
                $match->forceFill(['published_at' => Carbon::now()])->saveQuietly();
            } else {
                $stats['matches_updated']++;
            }
        }
    }

    /**
     * Club por nombre oficial completo (cacheado por corrida). Si el club aún
     * no tiene ciudad y el campeonato es provincial, se rellena best-effort
     * con la provincia ("CAMPEONATO PROVINCIAL - PICHINCHA" → "Pichincha").
     * Nunca pisa una ciudad ya definida (editable por el super admin).
     */
    private function club(string $name, ?Championship $championship, array &$cache, array &$stats): Club
    {
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $club = Club::firstOrCreate(['name' => $name]);

        if ($club->wasRecentlyCreated) {
            $stats['clubs']++;
        }

        if (empty($club->city) && $championship) {
            $city = $this->cityFromChampionship($championship->name);
            if ($city !== null) {
                $club->update(['city' => $city]);
            }
        }

        return $cache[$name] = $club;
    }

    /** "2026 - CAMPEONATO PROVINCIAL - EL ORO" → "El Oro" (null si no es provincial). */
    private function cityFromChampionship(string $championshipName): ?string
    {
        if (! preg_match('/CAMPEONATO PROVINCIAL\s*-\s*(.+)$/iu', $championshipName, $m)) {
            return null;
        }

        $province = trim($m[1]);

        return $province === '' ? null : mb_convert_case(mb_strtolower($province), MB_CASE_TITLE, 'UTF-8');
    }

    private function championshipByName(string $tournament, bool $dryRun): ?Championship
    {
        $tournament = trim($tournament);

        if ($tournament === '') {
            return null;
        }

        if ($dryRun) {
            return Championship::where('name', $tournament)->first();
        }

        $ch = Championship::firstOrCreate(
            ['name' => $tournament],
            [
                'season' => $this->seasonFromName($tournament),
                'category' => $this->categoryFromName($tournament),
                'is_active' => true,
            ]
        );

        return $ch->is_active ? $ch : null;
    }

    /** Oficiales publicados (terna + cuarto). Claves estables propias. */
    private function officialsFrom(array $m): ?array
    {
        $officials = array_filter([
            'center' => trim((string) ($m['center_referee'] ?? '')),
            'assistant_1' => trim((string) ($m['assistant_referee_1'] ?? '')),
            'assistant_2' => trim((string) ($m['assistant_referee_2'] ?? '')),
            'fourth' => trim((string) ($m['fourth_official'] ?? '')),
        ]);

        return $officials === [] ? null : $officials;
    }

    /** "2026 - FORMATIVA SUB 19" → season "2026". */
    private function seasonFromName(string $name): ?string
    {
        return preg_match('/\b(20\d{2})\b/', $name, $m) ? $m[1] : null;
    }

    /** Heurística simple de categoría a partir del nombre del torneo. */
    private function categoryFromName(string $name): ?string
    {
        $upper = mb_strtoupper($name);

        return match (true) {
            str_contains($upper, 'FORMATIVA') => 'formativa',
            str_contains($upper, 'PROVINCIAL') => 'segunda',
            str_contains($upper, 'FEMENIN') => 'femenino',
            str_contains($upper, 'FUTSAL') => 'futsal',
            str_contains($upper, 'COPA') => 'copa',
            default => null,
        };
    }
}
