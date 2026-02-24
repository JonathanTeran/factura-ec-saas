<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
        ]);

        $this->assertDatabaseHas('tenants', [
            'owner_email' => 'newuser@example.com',
        ]);
    }

    public function test_registration_requires_name(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'not-an-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword456!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_duplicate_email_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Another User',
            'company_name' => 'Another Company',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_with_plan_query_param(): void
    {
        $response = $this->get('/register?plan=negocio');

        $response->assertStatus(200);
    }

    public function test_registration_creates_tenant(): void
    {
        $tenantCountBefore = Tenant::count();

        $this->post('/register', [
            'name' => 'Tenant Owner',
            'company_name' => 'New Tenant Company',
            'email' => 'tenantowner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $this->assertGreaterThan($tenantCountBefore, Tenant::count());

        $user = User::where('email', 'tenantowner@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->tenant_id);

        $tenant = Tenant::find($user->tenant_id);
        $this->assertNotNull($tenant);
        $this->assertEquals('tenantowner@example.com', $tenant->owner_email);
    }
}
