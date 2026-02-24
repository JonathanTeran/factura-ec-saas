<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function confirmPassword(): void
    {
        $this->actingAs($this->user)
            ->post('/user/confirm-password', [
                'password' => 'password',
            ]);
    }

    public function test_can_enable_two_factor_authentication(): void
    {
        $this->confirmPassword();

        $response = $this->actingAs($this->user)
            ->postJson('/user/two-factor-authentication');

        $response->assertOk();

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);
    }

    public function test_can_disable_two_factor_authentication(): void
    {
        // Enable 2FA
        $this->confirmPassword();
        $this->actingAs($this->user)
            ->postJson('/user/two-factor-authentication');

        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);

        // Disable 2FA
        $this->confirmPassword();
        $response = $this->actingAs($this->user)
            ->deleteJson('/user/two-factor-authentication');

        $response->assertOk();

        $this->user->refresh();
        $this->assertNull($this->user->two_factor_secret);
    }

    public function test_two_factor_challenge_redirects_without_pending_login(): void
    {
        // Without a pending 2FA login, the challenge page should redirect
        $response = $this->get('/two-factor-challenge');

        $response->assertRedirect();
    }

    public function test_unauthenticated_user_cannot_enable_2fa(): void
    {
        $response = $this->post('/user/two-factor-authentication');

        $response->assertRedirect('/login');
    }
}
