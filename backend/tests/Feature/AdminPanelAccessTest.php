<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Acceso al panel /admin: super admin entra; una sesión de empresa se cierra y vuelve al login. */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'tenant_id' => null, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_tenant_session_is_logged_out_and_sent_to_admin_login_instead_of_403(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::TENANT_OWNER->value, 'tenant_id' => $tenant->id, 'is_active' => true]);

        $response = $this->actingAs($owner)->get('/admin');

        $response->assertRedirect('/admin/login');
        $this->assertGuest('web');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
