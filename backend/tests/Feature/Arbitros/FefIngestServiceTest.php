<?php

namespace Tests\Feature\Arbitros;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\FootballMatch;
use App\Services\Arbitros\FefIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de la ingesta del catálogo público FEF (campeonatos, clubes, partidos).
 * La API se simula con Http::fake imitando el envelope real
 * {status_code, success, message, data}.
 */
class FefIngestServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): FefIngestService
    {
        return app(FefIngestService::class);
    }

    /**
     * Fake de la API pública FEF. Responde:
     *  - /competitions/matches/recent/competitions → lista de competiciones
     *  - /competitions/matches/recent?hierarchy_id=X → partidos de esa competición
     *  - /competitions/matches/recent (sin filtro) → feed global
     */
    private function fakeFefApi(array $competitions, array $matchesByHierarchy = [], array $globalMatches = []): void
    {
        Http::fake(function (Request $request) use ($competitions, $matchesByHierarchy, $globalMatches) {
            $url = $request->url();

            $envelope = fn (array $data) => Http::response([
                'status_code' => 200,
                'success' => true,
                'message' => 'OK',
                'data' => $data,
            ]);

            if (str_contains($url, '/competitions/matches/recent/competitions')) {
                return $envelope($competitions);
            }

            if (str_contains($url, '/competitions/matches/recent')) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $hierarchyId = $query['hierarchy_id'] ?? null;

                if ($hierarchyId !== null) {
                    return $envelope($matchesByHierarchy[$hierarchyId] ?? []);
                }

                return $envelope($globalMatches);
            }

            return Http::response(['status_code' => 404, 'success' => false, 'message' => 'Not found', 'data' => null], 404);
        });
    }

    /** Partido con la forma del feed real de la FEF. */
    private function fefMatch(array $overrides = []): array
    {
        static $seq = 0;
        $seq++;

        return array_merge([
            'match_id' => "fef-match-{$seq}",
            'match_date' => now()->subDays(3)->toDateString(),
            'tournament' => '2026 - FORMATIVA SUB 19',
            'home_team' => 'CLUB SPORT EMELEC',
            'away_team' => 'BARCELONA SPORTING CLUB',
            'stage' => 'PRIMERA ETAPA',
            'center_referee' => 'POVEDA MONTANERO KEVIN ARIEL',
            'assistant_referee_1' => 'LOPEZ GARCIA JUAN CARLOS',
            'assistant_referee_2' => 'MARTINEZ RUIZ PEDRO JOSE',
            'fourth_official' => 'SANCHEZ VERA LUIS MIGUEL',
            'home_score' => 1,
            'away_score' => 0,
        ], $overrides);
    }

    public function test_sync_creates_championship_clubs_and_matches_with_officials(): void
    {
        $this->fakeFefApi(
            competitions: [
                [
                    'hierarchy_id' => 'h-sub19',
                    'competition_name' => '2026 - FORMATIVA SUB 19',
                    'path' => '/futbol-formativo/sub-19',
                ],
            ],
            matchesByHierarchy: [
                'h-sub19' => [
                    $this->fefMatch(['match_id' => 'm-1']),
                    $this->fefMatch([
                        'match_id' => 'm-2',
                        'home_team' => 'INDEPENDIENTE DEL VALLE',
                        'away_team' => 'LIGA DEPORTIVA UNIVERSITARIA',
                        'fourth_official' => '', // sin cuarto publicado
                    ]),
                ],
            ],
        );

        $stats = $this->service()->sync();

        $this->assertSame(1, $stats['championships']);
        $this->assertSame(4, $stats['clubs']);
        $this->assertSame(2, $stats['matches_created']);
        $this->assertSame(0, $stats['matches_updated']);
        $this->assertSame(0, $stats['skipped_inactive']);

        // Campeonato con season extraída del nombre y categoría heurística.
        $championship = Championship::where('external_ref', 'h-sub19')->first();
        $this->assertNotNull($championship);
        $this->assertSame('2026 - FORMATIVA SUB 19', $championship->name);
        $this->assertSame('2026', $championship->season);
        $this->assertSame('formativa', $championship->category);

        // Clubes por nombre oficial completo.
        $this->assertSame(4, Club::count());
        $this->assertNotNull(Club::where('name', 'CLUB SPORT EMELEC')->first());
        $this->assertNotNull(Club::where('name', 'INDEPENDIENTE DEL VALLE')->first());

        // Partido con officials json {center, assistant_1, assistant_2, fourth}.
        $match = FootballMatch::where('external_ref', 'm-1')->first();
        $this->assertNotNull($match);
        $this->assertSame($championship->id, $match->championship_id);
        $this->assertSame(Club::where('name', 'CLUB SPORT EMELEC')->value('id'), $match->home_club_id);
        $this->assertSame(Club::where('name', 'BARCELONA SPORTING CLUB')->value('id'), $match->away_club_id);
        $this->assertSame('PRIMERA ETAPA', $match->stage);
        $this->assertSame('scraper', $match->source);
        $this->assertNotNull($match->published_at);
        $this->assertSame([
            'center' => 'POVEDA MONTANERO KEVIN ARIEL',
            'assistant_1' => 'LOPEZ GARCIA JUAN CARLOS',
            'assistant_2' => 'MARTINEZ RUIZ PEDRO JOSE',
            'fourth' => 'SANCHEZ VERA LUIS MIGUEL',
        ], $match->officials);

        // El partido sin cuarto publicado no incluye la clave 'fourth'.
        $match2 = FootballMatch::where('external_ref', 'm-2')->first();
        $this->assertNotNull($match2);
        $this->assertArrayNotHasKey('fourth', $match2->officials);
        $this->assertSame('POVEDA MONTANERO KEVIN ARIEL', $match2->officials['center']);
    }

    public function test_sync_is_idempotent_second_run_updates_instead_of_duplicating(): void
    {
        $this->fakeFefApi(
            competitions: [
                ['hierarchy_id' => 'h-sub19', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/sub-19'],
            ],
            matchesByHierarchy: [
                'h-sub19' => [
                    $this->fefMatch(['match_id' => 'm-1']),
                    $this->fefMatch(['match_id' => 'm-2']),
                ],
            ],
        );

        $first = $this->service()->sync();
        $second = $this->service()->sync();

        $this->assertSame(2, $first['matches_created']);
        $this->assertSame(0, $first['matches_updated']);

        // Segunda corrida: todo es update, nada nuevo.
        $this->assertSame(0, $second['matches_created']);
        $this->assertSame(2, $second['matches_updated']);
        $this->assertSame(0, $second['clubs']);

        // Sin duplicados en BD.
        $this->assertSame(1, Championship::count());
        $this->assertSame(2, Club::count());
        $this->assertSame(2, FootballMatch::count());
    }

    public function test_sync_skips_matches_of_inactive_championship(): void
    {
        // El super admin desactivó este campeonato.
        Championship::create([
            'name' => '2026 - FORMATIVA SUB 19',
            'external_ref' => 'h-inactive',
            'is_active' => false,
        ]);
        // Campeonato activo ya conocido.
        Championship::create([
            'name' => '2026 - CAMPEONATO PROVINCIAL',
            'external_ref' => 'h-active',
            'is_active' => true,
        ]);

        $this->fakeFefApi(
            competitions: [
                ['hierarchy_id' => 'h-inactive', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/sub-19'],
                ['hierarchy_id' => 'h-active', 'competition_name' => '2026 - CAMPEONATO PROVINCIAL', 'path' => '/provincial'],
            ],
            matchesByHierarchy: [
                'h-inactive' => [$this->fefMatch(['match_id' => 'm-inactive-1'])],
                'h-active' => [$this->fefMatch(['match_id' => 'm-active-1', 'tournament' => '2026 - CAMPEONATO PROVINCIAL'])],
            ],
        );

        $stats = $this->service()->sync();

        $this->assertSame(2, $stats['championships']);
        $this->assertSame(1, $stats['skipped_inactive']);
        $this->assertSame(1, $stats['matches_created']);

        // Ningún partido del campeonato inactivo, y ni siquiera se consultó su feed.
        $this->assertNull(FootballMatch::where('external_ref', 'm-inactive-1')->first());
        $this->assertNotNull(FootballMatch::where('external_ref', 'm-active-1')->first());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'hierarchy_id=h-inactive'));
    }

    public function test_dry_run_writes_nothing_to_database(): void
    {
        // Campeonato pre-existente para que el dry run pueda contar partidos.
        Championship::create([
            'name' => '2026 - FORMATIVA SUB 19',
            'external_ref' => 'h-sub19',
            'is_active' => true,
        ]);

        $this->fakeFefApi(
            competitions: [
                ['hierarchy_id' => 'h-sub19', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/sub-19'],
            ],
            matchesByHierarchy: [
                'h-sub19' => [
                    $this->fefMatch(['match_id' => 'm-1']),
                    $this->fefMatch(['match_id' => 'm-2']),
                ],
            ],
        );

        $stats = $this->service()->sync(dryRun: true);

        // Cuenta lo que HARÍA…
        $this->assertSame(1, $stats['championships']);
        $this->assertSame(2, $stats['matches_created']);

        // …pero no escribe nada.
        $this->assertSame(1, Championship::count()); // solo el pre-existente
        $this->assertSame(0, Club::count());
        $this->assertSame(0, FootballMatch::count());
    }

    public function test_matches_without_id_or_teams_are_ignored_gracefully(): void
    {
        Championship::create([
            'name' => '2026 - FORMATIVA SUB 19',
            'external_ref' => 'h-sub19',
            'is_active' => true,
        ]);

        $this->fakeFefApi(
            competitions: [
                ['hierarchy_id' => 'h-sub19', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/sub-19'],
            ],
            matchesByHierarchy: [
                'h-sub19' => [
                    $this->fefMatch(['match_id' => 'm-valid']),
                    // Sin match_id: el cliente ni siquiera lo reconoce como partido.
                    collect($this->fefMatch())->except('match_id')->all(),
                    // match_id vacío.
                    $this->fefMatch(['match_id' => '']),
                    // Sin equipo local.
                    $this->fefMatch(['match_id' => 'm-no-home', 'home_team' => '']),
                    // Sin equipo visitante.
                    $this->fefMatch(['match_id' => 'm-no-away', 'away_team' => '   ']),
                ],
            ],
        );

        $stats = $this->service()->sync();

        $this->assertSame(1, $stats['matches_created']);
        $this->assertSame(1, FootballMatch::count());
        $this->assertNotNull(FootballMatch::where('external_ref', 'm-valid')->first());
    }

    public function test_global_feed_fallback_resolves_championship_by_tournament_name(): void
    {
        // Sin lista de competiciones: sync cae al feed global y resuelve el
        // campeonato por el nombre del torneo (creándolo activo si no existe).
        $this->fakeFefApi(
            competitions: [],
            globalMatches: [
                $this->fefMatch(['match_id' => 'm-global-1', 'tournament' => '2026 - CAMPEONATO PROVINCIAL']),
                // Sin torneo identificable → cuarentena (no se ingesta).
                $this->fefMatch(['match_id' => 'm-global-2', 'tournament' => '   ']),
            ],
        );

        $stats = $this->service()->sync();

        $this->assertSame(1, $stats['matches_created']);

        $championship = Championship::where('name', '2026 - CAMPEONATO PROVINCIAL')->first();
        $this->assertNotNull($championship);
        $this->assertTrue($championship->is_active);
        $this->assertSame('2026', $championship->season);
        $this->assertSame('segunda', $championship->category);

        $this->assertNotNull(FootballMatch::where('external_ref', 'm-global-1')->first());
        $this->assertNull(FootballMatch::where('external_ref', 'm-global-2')->first());
        $this->assertSame(1, FootballMatch::count());
    }

    public function test_first_sync_of_a_brand_new_championship_ingests_its_matches(): void
    {
        // BD vacía + sync() → el campeonato nuevo NO debe caer en skipped_inactive
        // y sus partidos deben ingerirse en la primera corrida.
        $this->fakeFefApi(
            competitions: [
                ['hierarchy_id' => 'h-new', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/sub-19'],
            ],
            matchesByHierarchy: ['h-new' => [$this->fefMatch(['match_id' => 'm-new-1'])]],
        );

        $stats = $this->service()->sync();

        $this->assertSame(0, $stats['skipped_inactive']);
        $this->assertSame(1, $stats['matches_created']);
        $this->assertSame(1, FootballMatch::count());
    }
}
