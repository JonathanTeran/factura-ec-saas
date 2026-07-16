<?php

namespace Tests\Feature\Arbitros;

use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reporte de control del árbitro: resumen (facturado/pendiente), por campeonato
 * y por mes, con export a Excel.
 */
class RefereeReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Championship $champA;
    private Championship $champB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['business_type' => Tenant::BUSINESS_TYPE_REFEREE]);
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($user);

        $this->champA = Championship::create(['name' => 'LIGA A', 'season' => '2026']);
        $this->champB = Championship::create(['name' => 'LIGA B', 'season' => '2026']);
        $home = Club::create(['name' => 'H']);
        $away = Club::create(['name' => 'V']);

        $make = function (Championship $ch, string $date, string $status, float $fee) use ($home, $away) {
            OfficiatedMatch::create([
                'tenant_id' => $this->tenant->id,
                'championship_id' => $ch->id,
                'home_club_id' => $home->id,
                'away_club_id' => $away->id,
                'match_date' => $date,
                'role' => 'arbitro',
                'fee' => $fee,
                'status' => $status,
            ]);
        };

        $make($this->champA, '2026-06-05', 'invoiced', 45);
        $make($this->champA, '2026-06-20', 'invoiced', 45);
        $make($this->champB, '2026-07-10', 'pending', 30);
        $make($this->champA, '2025-06-10', 'invoiced', 99); // otro año: no debe contar
    }

    public function test_report_aggregates_by_status_championship_and_month(): void
    {
        $res = $this->getJson('/api/v1/referee/report?year=2026')->assertOk();
        $data = $res->json('data');

        $this->assertSame(2, $data['summary']['invoiced']['count']);
        $this->assertEqualsWithDelta(90, $data['summary']['invoiced']['amount'], 0.001);
        $this->assertSame(1, $data['summary']['pending']['count']);
        $this->assertEqualsWithDelta(30, $data['summary']['total_pending'], 0.001);
        $this->assertEqualsWithDelta(90, $data['summary']['total_billed'], 0.001);
        $this->assertSame(3, $data['summary']['total_matches']);

        // Por campeonato: LIGA A primero (mayor monto).
        $this->assertCount(2, $data['by_championship']);
        $this->assertSame('LIGA A', $data['by_championship'][0]['championship']);
        $this->assertEqualsWithDelta(90, $data['by_championship'][0]['invoiced_amount'], 0.001);

        // Por mes: junio (2 facturados) y julio (1 pendiente).
        $months = collect($data['by_month'])->keyBy('month');
        $this->assertEqualsWithDelta(90, $months['2026-06']['invoiced_amount'], 0.001);
        $this->assertEqualsWithDelta(30, $months['2026-07']['pending_amount'], 0.001);

        $this->assertContains(2026, $data['available_years']);
        $this->assertContains(2025, $data['available_years']);
    }

    public function test_report_excel_export_downloads(): void
    {
        $res = $this->get('/api/v1/referee/report/export?year=2026');
        $res->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $res->headers->get('content-type')
        );
    }

    public function test_report_forbidden_for_generic_tenant(): void
    {
        $this->tenant->update(['business_type' => 'generic']);
        $this->getJson('/api/v1/referee/report')->assertStatus(403);
    }
}
