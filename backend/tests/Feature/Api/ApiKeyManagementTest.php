<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Tenant\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\UsesApiKeys;

/** Gestión de llaves desde el panel: /api/v1/api-keys (Sanctum). */
class ApiKeyManagementTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase, UsesApiKeys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->enableApiAccess();
    }

    public function test_index_lists_keys_scopes_and_limits(): void
    {
        $this->makeApiKey(['name' => 'Tienda']);

        $this->getJson('/api/v1/api-keys')
            ->assertOk()
            ->assertJsonCount(1, 'data.api_keys')
            ->assertJsonPath('data.api_keys.0.name', 'Tienda')
            ->assertJsonPath('data.limits.max_keys', 10)
            ->assertJsonPath('data.limits.plan_rate_limit', 60)
            ->assertJsonStructure(['data' => ['scopes', 'base_url', 'docs_url']])
            ->assertJsonMissingPath('data.api_keys.0.key_hash');
    }

    public function test_create_returns_the_plain_key_once(): void
    {
        $response = $this->postJson('/api/v1/api-keys', [
            'name' => 'ERP',
            'scopes' => ['documents:write', 'documents:read', 'customers:write'],
            'expires_in_days' => 90,
            'rate_limit_per_minute' => 500,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.api_key.name', 'ERP')
            ->assertJsonPath('data.api_key.scopes', ['customers:write', 'documents:read', 'documents:write'])
            ->assertJsonPath('data.api_key.rate_limit_per_minute', 60)
            ->assertJsonPath('data.api_key.is_active', true);

        $plain = $response->json('data.plain_key');
        $this->assertStringStartsWith('fec_', $plain);
        $this->assertSame(44, strlen($plain));
        $this->assertSame(substr($plain, 0, 12), $response->json('data.api_key.key_prefix'));
        $this->assertNotNull($response->json('data.api_key.expires_at'));

        $this->getJson('/api/v1/api-keys')->assertOk()->assertJsonMissing(['plain_key' => $plain]);

        // La llave sirve de inmediato en la API de integración.
        $this->extGet('/me', $plain)->assertOk();
        $this->extPost('/products', $plain, [])->assertStatus(403)->assertJsonPath('error', 'insufficient_scope');
    }

    public function test_create_validates_input(): void
    {
        $this->postJson('/api/v1/api-keys', ['name' => '', 'scopes' => ['nope'], 'expires_in_days' => 12])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'scopes.0', 'expires_in_days']);
    }

    public function test_max_ten_active_keys(): void
    {
        ApiKey::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);

        $this->postJson('/api/v1/api-keys', ['name' => 'Once', 'scopes' => ['*']])
            ->assertStatus(422)
            ->assertJsonPath('error', 'max_keys_reached');
    }

    public function test_only_owner_or_admin_can_manage_keys(): void
    {
        $viewer = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::VIEWER->value]);
        Sanctum::actingAs($viewer);

        $this->postJson('/api/v1/api-keys', ['name' => 'X', 'scopes' => ['*']])->assertStatus(403);
        $this->getJson('/api/v1/api-keys')->assertOk(); // consultar sí puede
    }

    public function test_plan_without_api_access_is_feature_not_available(): void
    {
        $this->plan->update(['has_api_access' => false]);
        $this->tenant->syncPlanLimits($this->plan->fresh());

        $this->getJson('/api/v1/api-keys')
            ->assertStatus(403)
            ->assertJsonPath('error', 'feature_not_available');
    }

    public function test_rotate_invalidates_the_previous_key(): void
    {
        [$key, $old] = $this->makeApiKey();
        $this->extGet('/me', $old)->assertOk();

        $response = $this->postJson('/api/v1/api-keys/'.$key->id.'/rotate')->assertOk();
        $new = $response->json('data.plain_key');

        $this->assertNotSame($old, $new);
        $this->extGet('/me', $old)->assertStatus(401);
        $this->extGet('/me', $new)->assertOk();
    }

    public function test_update_can_deactivate_and_change_scopes(): void
    {
        [$key, $plain] = $this->makeApiKey();

        $this->patchJson('/api/v1/api-keys/'.$key->id, ['scopes' => ['products:read'], 'name' => 'Solo lectura'])
            ->assertOk()
            ->assertJsonPath('data.api_key.scopes', ['products:read'])
            ->assertJsonPath('data.api_key.name', 'Solo lectura');

        $this->extGet('/documents', $plain)->assertStatus(403);

        $this->patchJson('/api/v1/api-keys/'.$key->id, ['is_active' => false])->assertOk();
        $this->extGet('/me', $plain)->assertStatus(401)->assertJsonPath('error', 'invalid_api_key');
    }

    public function test_delete_and_cross_tenant_404(): void
    {
        [$key] = $this->makeApiKey();
        ['tenant' => $other] = $this->createSecondTenant();
        $foreign = ApiKey::factory()->create(['tenant_id' => $other->id]);

        $this->deleteJson('/api/v1/api-keys/'.$foreign->id)->assertStatus(404);
        $this->deleteJson('/api/v1/api-keys/'.$key->id)->assertOk();
        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
    }
}
