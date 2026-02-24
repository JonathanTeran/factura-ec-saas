<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@factura-ec.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPER_ADMIN,
                'tenant_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign Spatie role
        $user->assignRole('admin');
    }
}
