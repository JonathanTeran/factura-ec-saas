<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea el super administrador SOLO si no existe ninguno. Nunca sobrescribe
 * el correo ni la contraseña de un admin ya existente (antes cada
 * `db:seed` volvía a poner la contraseña por defecto "password").
 *
 * Variables opcionales: SUPER_ADMIN_EMAIL, SUPER_ADMIN_NAME y
 * SUPER_ADMIN_PASSWORD. Sin contraseña se genera una aleatoria que no se
 * muestra: el acceso inicial se hace con "Olvidé mi contraseña" en /admin.
 */
class SuperAdminSeeder extends Seeder
{
    public const DEFAULT_EMAIL = 'admin@factura-ec.com';

    public function run(): void
    {
        $existing = User::withoutGlobalScopes()
            ->where('role', UserRole::SUPER_ADMIN->value)
            ->orderBy('id')
            ->first();

        if ($existing) {
            $this->ensureSpatieRole($existing);
            $this->command?->info("Super admin existente ({$existing->email}): credenciales sin cambios.");

            return;
        }

        $password = (string) env('SUPER_ADMIN_PASSWORD', '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::random(40);
        }

        $user = User::create([
            'name' => (string) env('SUPER_ADMIN_NAME', 'Super Administrador'),
            'email' => (string) env('SUPER_ADMIN_EMAIL', self::DEFAULT_EMAIL),
            'password' => Hash::make($password),
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->ensureSpatieRole($user);

        if ($generated) {
            $this->command?->warn(
                "Super admin creado ({$user->email}) con contraseña aleatoria: "
                .'usa "Olvidé mi contraseña" en /admin/login para definirla.'
            );
        }
    }

    protected function ensureSpatieRole(User $user): void
    {
        if (method_exists($user, 'assignRole') && ! $user->hasRole('admin')) {
            try {
                $user->assignRole('admin');
            } catch (\Throwable) {
                // El rol de Spatie lo crea otro seeder; no bloquear el arranque.
            }
        }
    }
}
