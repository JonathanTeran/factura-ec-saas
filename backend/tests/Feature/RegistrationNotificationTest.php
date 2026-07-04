<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Notifications\NewTenantRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

class RegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_notifies_configured_admin_emails(): void
    {
        Notification::fake();
        config(['notifications.admin_recipients' => ['a@ejemplo.com', 'b@ejemplo.com']]);

        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_OWNER,
        ]);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            NewTenantRegisteredNotification::class,
            function ($notification, $channels, $notifiable) {
                $route = $notifiable->routes['mail'] ?? [];
                return in_array('a@ejemplo.com', (array) $route, true)
                    && in_array('b@ejemplo.com', (array) $route, true);
            },
        );
    }

    public function test_non_owner_user_does_not_trigger_notification(): void
    {
        Notification::fake();
        config(['notifications.admin_recipients' => ['a@ejemplo.com']]);

        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::VIEWER,
        ]);

        Notification::assertNothingSent();
    }
}
