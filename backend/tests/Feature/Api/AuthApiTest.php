<?php

namespace Tests\Feature\Api;

use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $plan = Plan::factory()->create([
            'slug' => 'emprendedor',
            'is_active' => true,
            'sort_order' => 1,
            'max_documents_per_month' => 50,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'ACME Corp',
            'terms' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'token_type', 'expires_at'],
            ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);

        $tenant = $user->tenant;
        $this->assertNotNull($tenant);
        $this->assertSame('jane@example.com', $tenant->owner_email);
        $this->assertNotEmpty($tenant->uuid);
        $this->assertSame($plan->id, $tenant->current_plan_id);
        $this->assertSame($user->id, $tenant->owner_id);
        $this->assertSame($plan->max_documents_per_month, $tenant->max_documents_per_month);
    }

    public function test_registration_as_referee_activates_the_module(): void
    {
        config(['arbitros.fef.ruc' => '1790000000001', 'arbitros.fef.business_name' => 'FEDERACION ECUATORIANA DE FUTBOL', 'arbitros.fef.email' => 'fef@example.com']);
        Plan::factory()->create(['slug' => 'emprendedor', 'is_active' => true, 'sort_order' => 1]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Pito Fino',
            'email' => 'arbitro@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Pito Fino',
            'terms' => true,
            'business_type' => 'referee',
        ])->assertCreated()->assertJsonPath('data.user.tenant.business_type', 'referee');

        $tenant = User::where('email', 'arbitro@example.com')->firstOrFail()->tenant;
        $this->assertTrue($tenant->isReferee());
        $this->assertDatabaseHas('customers', ['tenant_id' => $tenant->id, 'identification' => '1790000000001']);

        // Valor inválido: 422; sin el campo: negocio por defecto.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'company_name' => 'X', 'terms' => true, 'business_type' => 'club',
        ])->assertStatus(422)->assertJsonValidationErrors(['business_type']);
    }

    public function test_registration_sends_welcome_email(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        Plan::factory()->create(['slug' => 'emprendedor', 'is_active' => true, 'sort_order' => 1]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nuevo Cliente',
            'email' => 'nuevo@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Nueva Empresa',
            'terms' => true,
        ])->assertCreated();

        $user = User::where('email', 'nuevo@example.com')->first();
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \App\Notifications\WelcomeTenantNotification::class,
        );
    }

    public function test_registration_requires_unique_email(): void
    {
        Plan::factory()->create(['slug' => 'emprendedor', 'is_active' => true, 'sort_order' => 1]);

        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'taken@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Another Co',
            'terms' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_login(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'token_type', 'expires_at'],
            ]);
    }

    public function test_super_admin_cannot_login_to_the_tenant_panel(): void
    {
        User::factory()->create([
            'email' => 'it@facturon.ec',
            'password' => Hash::make('Secret123!'),
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'it@facturon.ec',
            'password' => 'Secret123!',
            'device_name' => 'web',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'super administrador') && str_contains($m, '/admin'));
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable();
    }

    public function test_user_can_logout(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk();
    }

    public function test_user_can_refresh_token(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_at'],
            ]);
    }

    public function test_user_can_get_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    }
}
