<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El seeder del super admin no debe volver a poner la contraseña por defecto
 * en cada despliegue ni pisar un correo ya cambiado.
 */
class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_super_admin_with_a_random_password_when_none_exists(): void
    {
        $this->assertSame(0, User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN->value)->count());

        $this->seed(SuperAdminSeeder::class);

        $admin = User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN->value)->firstOrFail();
        $this->assertSame(SuperAdminSeeder::DEFAULT_EMAIL, $admin->email);
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->tenant_id);
        $this->assertFalse(Hash::check('password', $admin->password), 'ya no se siembra la contraseña por defecto');
    }

    public function test_does_not_overwrite_an_existing_super_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('S3gura!Clave'),
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
        ]);

        $this->seed(SuperAdminSeeder::class);

        $admin->refresh();
        $this->assertSame('owner@example.com', $admin->email);
        $this->assertTrue(Hash::check('S3gura!Clave', $admin->password));
        $this->assertSame(1, User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN->value)->count());
        $this->assertDatabaseMissing('users', ['email' => SuperAdminSeeder::DEFAULT_EMAIL]);
    }

    public function test_admin_panel_exposes_password_reset_and_profile(): void
    {
        $this->get('/admin/password-reset/request')->assertOk();

        // Perfil solo con sesión: el invitado va al login del panel.
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_inactive_super_admin_cannot_access_the_admin_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
            'is_active' => false,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('admin');
        $this->assertFalse($admin->canAccessPanel($panel));

        $admin->update(['is_active' => true]);
        $this->assertTrue($admin->fresh()->canAccessPanel($panel));
    }
}
