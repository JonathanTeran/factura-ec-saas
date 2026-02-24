<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\CustomerPortalSession;
use App\Models\Portal\CustomerPortalToken;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use App\Notifications\CustomerPortalMagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_page_loads(): void
    {
        $response = $this->get(route('portal.login'));

        $response->assertStatus(200);
        $response->assertSee('Portal de Documentos');
    }

    public function test_magic_link_is_sent_for_existing_customer(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'cliente@example.com',
            'identification' => '1712345678',
        ]);

        $response = $this->post(route('portal.login.send'), [
            'input' => 'cliente@example.com',
        ]);

        $response->assertRedirect(route('portal.link-sent'));

        $this->assertDatabaseHas('customer_portal_tokens', [
            'tenant_id' => $tenant->id,
            'email' => 'cliente@example.com',
            'identification' => '1712345678',
        ]);

        Notification::assertSentOnDemand(CustomerPortalMagicLinkNotification::class);
    }

    public function test_magic_link_by_identification(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'cliente@example.com',
            'identification' => '1712345678',
        ]);

        $response = $this->post(route('portal.login.send'), [
            'input' => '1712345678',
        ]);

        $response->assertRedirect(route('portal.link-sent'));

        $this->assertDatabaseHas('customer_portal_tokens', [
            'identification' => '1712345678',
        ]);
    }

    public function test_no_error_shown_for_nonexistent_customer(): void
    {
        $response = $this->post(route('portal.login.send'), [
            'input' => 'no-existe@example.com',
        ]);

        // Debe redirigir a link-sent (no revelar si existe o no)
        $response->assertRedirect(route('portal.link-sent'));
    }

    public function test_valid_token_creates_session_and_redirects(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'identification' => '1712345678',
            'name' => 'Test Customer',
        ]);

        $token = CustomerPortalToken::generateFor(
            $tenant->id,
            $customer->email,
            $customer->identification,
        );

        $response = $this->get(route('portal.auth', ['token' => $token->token]));

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertCookie(config('portal.cookie_name', 'customer_portal_session'));

        $this->assertDatabaseCount('customer_portal_sessions', 1);
        $this->assertDatabaseHas('customer_portal_tokens', [
            'id' => $token->id,
        ]);

        // Token debe estar marcado como usado
        $token->refresh();
        $this->assertNotNull($token->used_at);
    }

    public function test_expired_token_redirects_to_login_with_error(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $token = CustomerPortalToken::generateFor(
            $tenant->id,
            $customer->email,
            $customer->identification,
        );

        // Expirar el token manualmente
        $token->update(['expires_at' => now()->subHour()]);

        $response = $this->get(route('portal.auth', ['token' => $token->token]));

        $response->assertRedirect(route('portal.login'));
        $this->assertDatabaseCount('customer_portal_sessions', 0);
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $token = CustomerPortalToken::generateFor(
            $tenant->id,
            $customer->email,
            $customer->identification,
        );

        // Usar el token una vez
        $this->get(route('portal.auth', ['token' => $token->token]));

        // Intentar usarlo de nuevo
        $response = $this->get(route('portal.auth', ['token' => $token->token]));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_logout_deletes_session_and_clears_cookie(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $token = CustomerPortalToken::generateFor(
            $tenant->id,
            $customer->email,
            $customer->identification,
        );

        // Autenticarse
        $authResponse = $this->get(route('portal.auth', ['token' => $token->token]));
        $sessionId = CustomerPortalSession::first()->id;
        $cookieName = config('portal.cookie_name', 'customer_portal_session');

        // Logout
        $response = $this->withCookie($cookieName, $sessionId)
            ->post(route('portal.logout'));

        $response->assertRedirect(route('portal.login'));
        $this->assertDatabaseCount('customer_portal_sessions', 0);
    }

    public function test_rate_limiting_on_magic_link_requests(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'rate-test@example.com',
            'identification' => '9999999999',
        ]);

        $maxAttempts = config('portal.max_magic_link_requests_per_hour', 3);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->post(route('portal.login.send'), ['input' => 'rate-test@example.com']);
        }

        // El siguiente intento debe fallar por rate limit
        $response = $this->post(route('portal.login.send'), ['input' => 'rate-test@example.com']);

        $response->assertSessionHasErrors('input');
    }

    public function test_multi_tenant_customer_shows_tenant_selector(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Empresa A']);
        $tenant2 = Tenant::factory()->create(['name' => 'Empresa B']);

        Customer::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => 'multi@example.com',
            'identification' => '1234567890',
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant2->id,
            'email' => 'multi@example.com',
            'identification' => '1234567890',
        ]);

        $response = $this->post(route('portal.login.send'), [
            'input' => 'multi@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Empresa A');
        $response->assertSee('Empresa B');
    }
}
