<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Filament\Resources\ApiKeyResource;
use App\Filament\Resources\ApiKeyResource\Pages\CreateApiKey;
use App\Filament\Resources\ApiKeyResource\Pages\ViewApiKey;
use App\Models\Billing\Plan;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Super admin: alta y gestión de integraciones por API (llaves fec_) de cualquier cuenta. */
class ApiKeyAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
            'is_active' => true,
        ]);
    }

    private ?Plan $plan = null;

    private function tenant(array $attrs = []): Tenant
    {
        $plan = $this->plan ??= Plan::factory()->create(['slug' => 'negocio', 'has_api_access' => true]);

        return Tenant::factory()->create(array_merge([
            'name' => 'Ferretería El Tornillo',
            'current_plan_id' => $plan->id,
            'has_api_access' => true,
        ], $attrs));
    }

    public function test_list_shows_keys_of_every_tenant(): void
    {
        $a = $this->tenant(['name' => 'Cuenta Alfa']);
        $b = $this->tenant(['name' => 'Cuenta Beta']);
        ApiKey::factory()->create(['tenant_id' => $a->id, 'name' => 'ERP Alfa']);
        ApiKey::factory()->create(['tenant_id' => $b->id, 'name' => 'Tienda Beta']);

        $this->actingAs($this->superAdmin())
            ->get('/admin/api-keys')
            ->assertOk()
            ->assertSee('Cuenta Alfa')
            ->assertSee('ERP Alfa')
            ->assertSee('Cuenta Beta')
            ->assertSee('Tienda Beta');
    }

    public function test_admin_creates_a_key_for_a_tenant_and_sees_it_once(): void
    {
        $admin = $this->superAdmin();
        $tenant = $this->tenant(['has_api_access' => false]);

        Livewire::actingAs($admin)
            ->test(CreateApiKey::class)
            ->fillForm([
                'tenant_id' => $tenant->id,
                'grant_api_access' => true,
                'name' => 'Contífico ERP',
                'permissions' => ['documents:read', 'documents:write'],
                'rate_limit_per_minute' => 100,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $key = ApiKey::withoutGlobalScopes()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('Contífico ERP', $key->name);
        $this->assertSame(['documents:read', 'documents:write'], $key->permissions);
        $this->assertSame(100, $key->rate_limit_per_minute);
        $this->assertSame($admin->id, $key->created_by);
        $this->assertTrue($key->is_active);
        // La cuenta no tenía API en su plan: el admin la habilitó desde el alta.
        $this->assertTrue($tenant->fresh()->has_api_access);

        $plain = session(ApiKeyResource::plainKeySessionKey($key));
        $this->assertStringStartsWith('fec_', $plain);
        $this->assertSame($key->key_hash, ApiKey::hashKey($plain));

        // La vista muestra la llave una sola vez y luego desaparece de la sesión.
        $this->actingAs($admin)
            ->get("/admin/api-keys/{$key->id}")
            ->assertOk()
            ->assertSee($plain)
            ->assertSee('/api/v1/ext');
        $this->assertNull(session(ApiKeyResource::plainKeySessionKey($key)));
    }

    public function test_rotate_replaces_the_credential(): void
    {
        $admin = $this->superAdmin();
        $tenant = $this->tenant();
        $key = ApiKey::factory()->create(['tenant_id' => $tenant->id]);
        $oldHash = $key->key_hash;

        Livewire::actingAs($admin)
            ->test(ViewApiKey::class, ['record' => $key->getRouteKey()])
            ->callAction('rotate')
            ->assertHasNoActionErrors();

        $key->refresh();
        $this->assertNotSame($oldHash, $key->key_hash);
        $plain = session(ApiKeyResource::plainKeySessionKey($key));
        $this->assertSame($key->key_hash, ApiKey::hashKey($plain));
        $this->assertSame(substr($plain, 0, 12), $key->key_prefix);
    }

    public function test_toggle_deactivates_and_reactivates(): void
    {
        $admin = $this->superAdmin();
        $key = ApiKey::factory()->create(['tenant_id' => $this->tenant()->id]);

        Livewire::actingAs($admin)
            ->test(ViewApiKey::class, ['record' => $key->getRouteKey()])
            ->callAction('toggle')
            ->assertHasNoActionErrors();
        $this->assertFalse($key->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(ViewApiKey::class, ['record' => $key->getRouteKey()])
            ->callAction('toggle');
        $this->assertTrue($key->fresh()->is_active);
    }
}
