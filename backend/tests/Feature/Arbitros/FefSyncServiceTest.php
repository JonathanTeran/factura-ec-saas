<?php

namespace Tests\Feature\Arbitros;

use App\Enums\UserRole;
use App\Jobs\Arbitros\SyncFefMatchesJob;
use App\Models\Arbitros\FefReferee;
use App\Models\Arbitros\FefSyncRun;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Notifications\AdminEventNotification;
use App\Services\Arbitros\FefIngestService;
use App\Services\Arbitros\FefSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * Orquestación observable de la sincronización FEF: historial de corridas,
 * directorio de árbitros, estado parcial por endpoints caídos y alertas al
 * super admin por fallo o por atraso.
 */
class FefSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFefApi(array $competitions, array $matchesByHierarchy = [], bool $failCompetitions = false): void
    {
        Http::fake(function (Request $request) use ($competitions, $matchesByHierarchy, $failCompetitions) {
            $url = $request->url();
            $envelope = fn (array $data) => Http::response(['status_code' => 200, 'success' => true, 'message' => 'OK', 'data' => $data]);

            if (str_contains($url, '/competitions/matches/recent/competitions')) {
                return $failCompetitions ? Http::response('upstream down', 503) : $envelope($competitions);
            }

            if (str_contains($url, '/competitions/matches/recent')) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $hierarchyId = $query['hierarchy_id'] ?? null;

                if ($hierarchyId === null) {
                    return Http::response('upstream down', 503);
                }

                return $envelope($matchesByHierarchy[$hierarchyId] ?? []);
            }

            return Http::response(['success' => false, 'data' => null], 404);
        });
    }

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
        ], $overrides);
    }

    private function refereeTenant(string $name = 'Kevin Ariel Póveda Montanero'): Tenant
    {
        return Tenant::factory()->create([
            'status' => 'active',
            'business_type' => Tenant::BUSINESS_TYPE_REFEREE,
            'settings' => ['referee_name' => $name, 'referee_default_fee' => 50],
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_a_successful_run_is_recorded_with_stats_and_referee_directory(): void
    {
        Notification::fake();
        $this->refereeTenant();
        $admin = $this->superAdmin();
        $this->fakeFefApi(
            [['hierarchy_id' => 'h1', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/x']],
            ['h1' => [
                $this->fefMatch(),
                $this->fefMatch(['match_id' => 'fef-match-b', 'center_referee' => 'LOPEZ GARCIA JUAN CARLOS', 'assistant_referee_1' => 'POVEDA MONTANERO KEVIN ARIEL']),
            ]],
        );

        $run = app(FefSyncService::class)->run(FefSyncRun::TRIGGER_MANUAL, $admin->id);

        $this->assertSame(FefSyncRun::STATUS_SUCCESS, $run->status);
        $this->assertSame(FefSyncRun::TRIGGER_MANUAL, $run->trigger);
        $this->assertSame($admin->id, $run->triggered_by);
        $this->assertNotNull($run->finished_at);
        $this->assertSame(1, $run->stat('championships'));
        $this->assertSame(2, $run->stat('matches_created'));
        $this->assertSame(2, $run->stat('clubs'));
        $this->assertSame(2, $run->stat('proposals'), 'central en un partido y asistente en el otro');
        $this->assertSame(4, $run->stat('referees'), 'cuatro nombres distintos de oficiales');
        $this->assertSame(1, $run->stat('referees_linked'));
        $this->assertSame([], $run->api_errors);

        $poveda = FefReferee::where('name', 'POVEDA MONTANERO KEVIN ARIEL')->firstOrFail();
        $this->assertSame(2, $poveda->matches_count);
        $this->assertSame(['center' => 1, 'assistant_1' => 1], $poveda->roles);
        $this->assertNotNull($poveda->tenant_id, 'el nombre configurado del árbitro se vincula a su cuenta');
        $this->assertNull(FefReferee::where('name', 'SANCHEZ VERA LUIS MIGUEL')->firstOrFail()->tenant_id);

        Notification::assertNothingSent();
    }

    public function test_directory_refresh_is_idempotent_and_drops_names_no_longer_present(): void
    {
        $this->refereeTenant();
        $this->fakeFefApi(
            [['hierarchy_id' => 'h1', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/x']],
            ['h1' => [$this->fefMatch()]],
        );

        app(FefSyncService::class)->run(FefSyncRun::TRIGGER_CLI);
        app(FefSyncService::class)->run(FefSyncRun::TRIGGER_CLI);

        $this->assertSame(4, FefReferee::count());
        $this->assertSame(2, FefSyncRun::count());
    }

    public function test_fef_endpoints_down_leave_the_run_partial_with_the_errors_listed(): void
    {
        Notification::fake();
        $this->refereeTenant();
        $this->fakeFefApi([], [], failCompetitions: true);

        $run = app(FefSyncService::class)->run();

        $this->assertSame(FefSyncRun::STATUS_PARTIAL, $run->status);
        $this->assertNotEmpty($run->api_errors);
        $this->assertStringContainsString('HTTP 503', $run->api_errors[0]['reason']);
        $this->assertSame(0, $run->stat('matches_created'));
        Notification::assertNothingSent();
    }

    public function test_an_exception_marks_the_run_failed_and_alerts_admins_only_on_transition(): void
    {
        Notification::fake();
        $this->refereeTenant();
        $admin = $this->superAdmin();
        $this->mock(FefIngestService::class)->shouldReceive('sync')->andThrow(new RuntimeException('FEF cambió el esquema'));

        try {
            app(FefSyncService::class)->run();
            $this->fail('debía relanzar la excepción para que Horizon reintente');
        } catch (RuntimeException) {
        }

        $run = FefSyncRun::latest();
        $this->assertSame(FefSyncRun::STATUS_FAILED, $run->status);
        $this->assertSame('FEF cambió el esquema', $run->error_message);
        Notification::assertSentTo($admin, AdminEventNotification::class, fn ($n) => $n->event === 'fef_sync.failed');

        // Segunda corrida fallida seguida: no se repite el aviso.
        try {
            app(FefSyncService::class)->run();
        } catch (RuntimeException) {
        }

        Notification::assertSentToTimes($admin, AdminEventNotification::class, 1);
        $this->assertSame(2, FefSyncRun::where('status', FefSyncRun::STATUS_FAILED)->count());
    }

    public function test_health_check_alerts_when_there_is_no_recent_successful_run(): void
    {
        Notification::fake();
        $this->refereeTenant();
        $admin = $this->superAdmin();

        $this->assertFalse(app(FefSyncService::class)->checkHealth());
        Notification::assertSentTo($admin, AdminEventNotification::class, fn ($n) => $n->event === 'fef_sync.stale');

        FefSyncRun::create(['trigger' => 'schedule', 'status' => FefSyncRun::STATUS_SUCCESS, 'started_at' => now()->subMinutes(30), 'finished_at' => now()->subMinutes(29)]);
        $this->assertTrue(app(FefSyncService::class)->checkHealth());

        $this->artisan('arbitros:sync-health')->assertSuccessful();
    }

    public function test_health_check_is_quiet_without_referee_tenants(): void
    {
        Notification::fake();
        $this->superAdmin();

        $this->assertTrue(app(FefSyncService::class)->checkHealth());
        Notification::assertNothingSent();
    }

    public function test_job_skips_without_referee_tenants_and_records_a_run_otherwise(): void
    {
        $this->fakeFefApi([['hierarchy_id' => 'h1', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/x']], ['h1' => [$this->fefMatch()]]);

        (new SyncFefMatchesJob)->handle(app(FefSyncService::class));
        $this->assertSame(0, FefSyncRun::count());

        $this->refereeTenant();
        (new SyncFefMatchesJob(FefSyncRun::TRIGGER_SCHEDULE))->handle(app(FefSyncService::class));

        $run = FefSyncRun::latest();
        $this->assertSame(FefSyncRun::TRIGGER_SCHEDULE, $run->trigger);
        $this->assertSame(FefSyncRun::STATUS_SUCCESS, $run->status);
    }

    public function test_cli_command_records_the_run_and_dry_run_does_not(): void
    {
        $this->refereeTenant();
        $this->fakeFefApi([['hierarchy_id' => 'h1', 'competition_name' => '2026 - FORMATIVA SUB 19', 'path' => '/x']], ['h1' => [$this->fefMatch()]]);

        $this->artisan('arbitros:sync-matches --dry-run')->assertSuccessful();
        $this->assertSame(0, FefSyncRun::count());

        $this->artisan('arbitros:sync-matches')->assertSuccessful();
        $this->assertSame(1, FefSyncRun::count());
        $this->assertSame(FefSyncRun::TRIGGER_CLI, FefSyncRun::latest()->trigger);
    }
}
