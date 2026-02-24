<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Enums\UserRole;

$user = User::where('email', 'admin@factura-ec.com')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User found: " . $user->name . "\n";
print_r($user->toArray());
echo "Role (Enum): " . ($user->role?->value ?? 'null') . "\n";
echo "Is Super Admin? " . ($user->isSuperAdmin() ? 'YES' : 'NO') . "\n";

$panel = new \Filament\Panel();
$panel->id('admin');

echo "Can Access Admin Panel? " . ($user->canAccessPanel($panel) ? 'YES' : 'NO') . "\n";

echo "Spatie Roles: " . $user->getRoleNames()->implode(', ') . "\n";
