<?php

namespace Tests\Feature\Arbitros;

use App\Enums\UserRole;
use App\Models\Arbitros\FefReferee;
use App\Models\Arbitros\FefSyncRun;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pantallas del super admin para la sincronización FEF y el directorio de árbitros. */
class FefAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_sync_history_page_lists_runs_with_status_and_counts(): void
    {
        FefSyncRun::create([
            'trigger' => FefSyncRun::TRIGGER_SCHEDULE,
            'status' => FefSyncRun::STATUS_PARTIAL,
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
            'duration_ms' => 42000,
            'stats' => ['championships' => 31, 'matches_created' => 12, 'matches_updated' => 500, 'clubs' => 2, 'proposals' => 3, 'referees' => 120, 'referees_linked' => 4],
            'api_errors' => [['path' => '/competitions/matches/recent', 'reason' => 'HTTP 503']],
        ]);

        $this->actingAs($this->superAdmin())
            ->get('/admin/fef-sync-runs')
            ->assertOk()
            ->assertSee('Sincronizaciones FEF')
            ->assertSee('Con avisos')
            ->assertSee('Programada')
            ->assertSee('Sincronizar ahora');
    }

    public function test_referee_directory_page_shows_referees_and_linked_accounts(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active', 'business_type' => Tenant::BUSINESS_TYPE_REFEREE, 'name' => 'Kevin Póveda']);
        FefReferee::create(['name' => 'POVEDA MONTANERO KEVIN ARIEL', 'normalized_name' => 'ARIEL KEVIN MONTANERO POVEDA', 'matches_count' => 7, 'roles' => ['center' => 5, 'fourth' => 2], 'first_seen_at' => now()->subMonths(2), 'last_seen_at' => now()->subDays(3), 'tenant_id' => $tenant->id]);
        FefReferee::create(['name' => 'SANCHEZ VERA LUIS MIGUEL', 'normalized_name' => 'LUIS MIGUEL SANCHEZ VERA', 'matches_count' => 1, 'roles' => ['assistant_1' => 1], 'first_seen_at' => now()->subDays(3), 'last_seen_at' => now()->subDays(3)]);

        $this->actingAs($this->superAdmin())
            ->get('/admin/fef-referees')
            ->assertOk()
            ->assertSee('POVEDA MONTANERO KEVIN ARIEL')
            ->assertSee('Kevin Póveda')
            ->assertSee('Sin cuenta');
    }

    public function test_dashboard_shows_the_fef_sync_widget_only_with_referee_tenants(): void
    {
        $admin = $this->superAdmin();

        // El menú lateral siempre lista la sección; lo que cambia es el widget del dashboard.
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('partidos detectados sin confirmar');

        Tenant::factory()->create(['status' => 'active', 'business_type' => Tenant::BUSINESS_TYPE_REFEREE]);
        FefSyncRun::create(['trigger' => 'schedule', 'status' => FefSyncRun::STATUS_SUCCESS, 'started_at' => now()->subMinutes(5), 'finished_at' => now()->subMinutes(4)]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('partidos detectados sin confirmar')->assertSee('Correcta');
    }

    public function test_tenant_users_cannot_open_the_admin_pages(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::TENANT_OWNER->value]);

        $this->actingAs($user)->get('/admin/fef-sync-runs')->assertForbidden();
    }
}
