<?php

namespace Tests\Feature\Arbitros;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\FootballMatch;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\Tenant\Tenant;
use App\Services\Arbitros\RefereeMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests del auto-matching de árbitros: cruza los oficiales publicados en los
 * partidos del catálogo contra settings.referee_name de cada tenant árbitro y
 * genera propuestas officiated_matches (pending).
 *
 * OfficiatedMatch tiene global scope de tenant basado en auth(); todas las
 * aserciones consultan con OfficiatedMatch::withoutTenantScope().
 */
class RefereeMatcherTest extends TestCase
{
    use RefreshDatabase;

    private Championship $championship;
    private Club $home;
    private Club $away;

    protected function setUp(): void
    {
        parent::setUp();

        $this->championship = Championship::create([
            'name' => '2026 - CAMPEONATO PROVINCIAL',
            'season' => '2026',
            'category' => 'segunda',
        ]);
        $this->home = Club::create(['name' => 'CLUB SPORT EMELEC']);
        $this->away = Club::create(['name' => 'BARCELONA SPORTING CLUB']);
    }

    private function matcher(): RefereeMatcher
    {
        return app(RefereeMatcher::class);
    }

    /** Partido del catálogo con oficiales publicados. */
    private function createMatch(array $officials, ?Carbon $date = null): FootballMatch
    {
        static $seq = 0;
        $seq++;

        return FootballMatch::create([
            'championship_id' => $this->championship->id,
            'home_club_id' => $this->home->id,
            'away_club_id' => $this->away->id,
            'match_date' => ($date ?? now()->subDays(3))->toDateString(),
            'external_ref' => "match-{$seq}",
            'officials' => $officials,
            'source' => 'scraper',
        ]);
    }

    /** Tenant árbitro configurado (business_type=referee + settings.referee_name). */
    private function refereeTenant(string $refereeName, array $extraSettings = []): Tenant
    {
        return Tenant::factory()->create([
            'business_type' => Tenant::BUSINESS_TYPE_REFEREE,
            'settings' => array_merge(['referee_name' => $refereeName], $extraSettings),
        ]);
    }

    public function test_normalize_ignores_accents_order_and_extra_spaces(): void
    {
        $matcher = $this->matcher();

        // El árbitro escribe "Nombre Apellidos"; la FEF publica "APELLIDOS NOMBRE".
        $this->assertSame(
            $matcher->normalize('Kevin Póveda Montanero'),
            $matcher->normalize('POVEDA MONTANERO KEVIN')
        );

        // Acentos y eñes.
        $this->assertSame(
            $matcher->normalize('José Núñez Ibáñez'),
            $matcher->normalize('NUNEZ IBANEZ JOSE')
        );

        // Espacios múltiples y bordes.
        $this->assertSame(
            $matcher->normalize('  KEVIN    POVEDA  '),
            $matcher->normalize('Kevin Póveda')
        );

        // Nombres distintos NO colisionan.
        $this->assertNotSame(
            $matcher->normalize('Kevin Poveda'),
            $matcher->normalize('Juan Poveda')
        );
    }

    public function test_detects_role_from_the_field_where_the_name_appears(): void
    {
        $tenant = $this->refereeTenant('Kevin Póveda Montanero');
        $other = 'RODRIGUEZ SALAZAR MARIO';

        $asCenter = $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN', 'assistant_1' => $other]);
        $asAssistant1 = $this->createMatch(['center' => $other, 'assistant_1' => 'POVEDA MONTANERO KEVIN']);
        $asAssistant2 = $this->createMatch(['assistant_2' => 'POVEDA MONTANERO KEVIN']);
        $asFourth = $this->createMatch(['fourth' => 'POVEDA MONTANERO KEVIN']);

        $stats = $this->matcher()->run();

        $this->assertSame(1, $stats['tenants']);
        $this->assertSame(4, $stats['proposals']);

        $roleFor = fn (FootballMatch $m) => OfficiatedMatch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('football_match_id', $m->id)
            ->value('role');

        $this->assertSame(OfficiatedMatch::ROLE_ARBITRO, $roleFor($asCenter));
        $this->assertSame(OfficiatedMatch::ROLE_ASISTENTE_1, $roleFor($asAssistant1));
        $this->assertSame(OfficiatedMatch::ROLE_ASISTENTE_2, $roleFor($asAssistant2));
        $this->assertSame(OfficiatedMatch::ROLE_CUARTO, $roleFor($asFourth));

        // Ninguna propuesta para el otro oficial (no hay tenant con ese nombre).
        $this->assertSame(4, OfficiatedMatch::withoutTenantScope()->count());
    }

    public function test_run_twice_creates_a_single_proposal_per_tenant_match_role(): void
    {
        $tenant = $this->refereeTenant('Kevin Póveda Montanero');
        $match = $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN']);

        $first = $this->matcher()->run();
        $second = $this->matcher()->run();

        $this->assertSame(1, $first['proposals']);
        $this->assertSame(0, $second['proposals']);

        $this->assertSame(1, OfficiatedMatch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('football_match_id', $match->id)
            ->count());
    }

    public function test_ignores_generic_tenants_and_referees_without_configured_name(): void
    {
        // Tenant generic (aunque tenga referee_name en settings, no es árbitro).
        Tenant::factory()->create([
            'business_type' => Tenant::BUSINESS_TYPE_GENERIC,
            'settings' => ['referee_name' => 'Kevin Póveda Montanero'],
        ]);

        // Tenant árbitro SIN nombre configurado.
        Tenant::factory()->create([
            'business_type' => Tenant::BUSINESS_TYPE_REFEREE,
            'settings' => [],
        ]);

        // Tenant árbitro con nombre vacío.
        Tenant::factory()->create([
            'business_type' => Tenant::BUSINESS_TYPE_REFEREE,
            'settings' => ['referee_name' => '   '],
        ]);

        // Único tenant configurado de verdad.
        $configured = $this->refereeTenant('Kevin Póveda Montanero');

        $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN']);

        $stats = $this->matcher()->run();

        // Solo cuenta los tenants árbitro con referee_name configurado
        // (filled() descarta el nombre en blanco).
        $this->assertSame(1, $stats['tenants']);
        $this->assertSame(1, $stats['proposals']);

        $proposals = OfficiatedMatch::withoutTenantScope()->get();
        $this->assertCount(1, $proposals);
        $this->assertSame($configured->id, $proposals->first()->tenant_id);
    }

    public function test_since_days_window_excludes_older_matches(): void
    {
        $this->refereeTenant('Kevin Póveda Montanero');

        // Partido de hace 10 días.
        $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN'], now()->subDays(10));

        // Ventana de 5 días: fuera de rango, sin propuestas.
        $narrow = $this->matcher()->run(sinceDays: 5);
        $this->assertSame(0, $narrow['proposals']);
        $this->assertSame(0, OfficiatedMatch::withoutTenantScope()->count());

        // Ventana de 30 días: entra.
        $wide = $this->matcher()->run(sinceDays: 30);
        $this->assertSame(1, $wide['proposals']);
        $this->assertSame(1, OfficiatedMatch::withoutTenantScope()->count());
    }

    public function test_dry_run_counts_proposals_but_writes_nothing(): void
    {
        $this->refereeTenant('Kevin Póveda Montanero');
        $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN']);

        $stats = $this->matcher()->run(dryRun: true);

        $this->assertSame(1, $stats['proposals']);
        $this->assertSame(0, OfficiatedMatch::withoutTenantScope()->count());
    }

    public function test_proposal_copies_match_data_and_gets_pending_scraper_defaults(): void
    {
        $tenant = $this->refereeTenant('Kevin Póveda Montanero', ['referee_default_fee' => 35.5]);
        $matchDate = now()->subDays(4);
        $match = $this->createMatch(['center' => 'POVEDA MONTANERO KEVIN'], $matchDate);

        $this->matcher()->run();

        $proposal = OfficiatedMatch::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->first();

        $this->assertNotNull($proposal);
        $this->assertSame($match->id, $proposal->football_match_id);
        $this->assertSame($this->championship->id, $proposal->championship_id);
        $this->assertSame($this->home->id, $proposal->home_club_id);
        $this->assertSame($this->away->id, $proposal->away_club_id);
        $this->assertSame($matchDate->toDateString(), $proposal->match_date->toDateString());
        $this->assertSame(OfficiatedMatch::STATUS_PENDING, $proposal->status);
        $this->assertSame('scraper', $proposal->source);
        $this->assertSame('35.50', (string) $proposal->fee);
        $this->assertNull($proposal->electronic_document_id);
    }
}
