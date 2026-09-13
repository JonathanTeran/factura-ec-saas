<?php

namespace Tests\Feature\Api\Ext;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\UsesApiKeys;

/** Autenticación, gate de plan, alcances y rate limit de /api/v1/ext. */
class ExternalApiAuthTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase, UsesApiKeys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->enableApiAccess();
    }

    public function test_missing_key_returns_401(): void
    {
        $this->getJson('/api/v1/ext/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'missing_api_key');
    }

    public function test_query_string_key_is_ignored(): void
    {
        [, $plain] = $this->makeApiKey();

        $this->getJson('/api/v1/ext/me?api_key='.$plain)
            ->assertStatus(401)
            ->assertJsonPath('error', 'missing_api_key');
    }

    public function test_invalid_key_returns_401(): void
    {
        $this->extGet('/me', 'fec_'.str_repeat('x', 40))
            ->assertStatus(401)
            ->assertJsonPath('error', 'invalid_api_key');
    }

    public function test_inactive_key_is_invalid(): void
    {
        [, $plain] = $this->makeApiKey(['is_active' => false]);

        $this->extGet('/me', $plain)->assertStatus(401)->assertJsonPath('error', 'invalid_api_key');
    }

    public function test_expired_key_returns_401_expired(): void
    {
        [, $plain] = $this->makeApiKey(['expires_at' => now()->subDay()]);

        $this->extGet('/me', $plain)->assertStatus(401)->assertJsonPath('error', 'expired_api_key');
    }

    public function test_bearer_and_x_api_key_headers_work(): void
    {
        [, $plain] = $this->makeApiKey();

        $this->extGet('/me', $plain)->assertOk()->assertJsonPath('success', true);

        $this->getJson('/api/v1/ext/me', ['X-API-Key' => $plain])
            ->assertOk()
            ->assertJsonPath('data.api_key.key_prefix', substr($plain, 0, 12));
    }

    public function test_plan_without_api_access_returns_403_with_upgrade_url(): void
    {
        [, $plain] = $this->makeApiKey();
        $this->plan->update(['has_api_access' => false]);
        $this->tenant->syncPlanLimits($this->plan->fresh());

        $this->extGet('/me', $plain)
            ->assertStatus(403)
            ->assertJsonPath('error', 'api_access_not_allowed')
            ->assertJson(fn ($json) => $json->where('upgrade_url', fn ($url) => str_ends_with($url, '/settings/subscription'))->etc());
    }

    public function test_without_active_subscription_returns_403(): void
    {
        [, $plain] = $this->makeApiKey();
        $this->subscription->update(['status' => SubscriptionStatus::EXPIRED]);

        $this->extGet('/me', $plain)->assertStatus(403)->assertJsonPath('error', 'subscription_required');
    }

    public function test_suspended_tenant_returns_403(): void
    {
        [, $plain] = $this->makeApiKey();
        $this->tenant->update(['status' => TenantStatus::SUSPENDED]);

        $this->extGet('/me', $plain)->assertStatus(403)->assertJsonPath('error', 'tenant_inactive');
    }

    public function test_scopes_are_enforced(): void
    {
        [, $plain] = $this->makeApiKey(['permissions' => ['customers:read']]);

        $this->extGet('/documents', $plain)
            ->assertStatus(403)
            ->assertJsonPath('error', 'insufficient_scope')
            ->assertJsonPath('required_scope', 'documents:read');

        $this->extGet('/customers', $plain)->assertOk();
        $this->extGet('/catalogs/tax-rates', $plain)->assertOk(); // catálogos: implícito
        $this->extPost('/customers', $plain, [])->assertStatus(403)->assertJsonPath('error', 'insufficient_scope');
    }

    public function test_wildcard_scope_allows_everything(): void
    {
        [, $plain] = $this->makeApiKey(['permissions' => ['*']]);

        $this->extGet('/documents', $plain)->assertOk();
        $this->extGet('/products', $plain)->assertOk();
    }

    public function test_rate_limit_headers_and_429(): void
    {
        [, $plain] = $this->makeApiKey(['rate_limit_per_minute' => 2]);

        $this->extGet('/me', $plain)->assertOk()
            ->assertHeader('X-RateLimit-Limit', '2')
            ->assertHeader('X-RateLimit-Remaining', '1');
        $this->extGet('/me', $plain)->assertOk()->assertHeader('X-RateLimit-Remaining', '0');

        $this->extGet('/me', $plain)
            ->assertStatus(429)
            ->assertJsonPath('error', 'rate_limit_exceeded')
            ->assertHeader('Retry-After');
    }

    public function test_plan_caps_the_key_rate_limit(): void
    {
        [, $plain] = $this->makeApiKey(['rate_limit_per_minute' => 500]);

        $this->extGet('/me', $plain)->assertOk()->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_usage_is_recorded(): void
    {
        [$key, $plain] = $this->makeApiKey();

        $this->extGet('/me', $plain)->assertOk();

        $key->refresh();
        $this->assertNotNull($key->last_used_at);
        $this->assertSame('127.0.0.1', $key->last_used_ip);
    }

    public function test_falls_back_to_an_admin_when_the_owner_is_missing(): void
    {
        [, $plain] = $this->makeApiKey();
        User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::ADMIN->value, 'is_active' => true]);
        $this->tenant->update(['owner_id' => null]);

        $this->extGet('/me', $plain)->assertOk()->assertJsonPath('data.tenant.id', $this->tenant->id);
    }

    public function test_key_from_another_tenant_only_sees_its_own_data(): void
    {
        $customer = $this->createCustomer();
        ['tenant' => $other, 'user' => $otherUser] = $this->createSecondTenant();
        $other->update(['current_plan_id' => $this->plan->id]);
        $other->syncPlanLimits($this->plan->fresh());
        \App\Models\Billing\Subscription::factory()->active()->monthly()->create(['tenant_id' => $other->id, 'plan_id' => $this->plan->id]);
        $plain = \App\Models\Tenant\ApiKey::generatePlainKey();
        \App\Models\Tenant\ApiKey::factory()->withPlainKey($plain)->create(['tenant_id' => $other->id, 'created_by' => $otherUser->id]);

        $this->extGet('/customers/'.$customer->id, $plain)->assertStatus(404)->assertJsonPath('error', 'not_found');
        $this->extGet('/customers', $plain)->assertOk()->assertJsonCount(0, 'data');
    }
}
