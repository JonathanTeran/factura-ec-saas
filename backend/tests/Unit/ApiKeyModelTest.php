<?php

namespace Tests\Unit;

use App\Models\Billing\Plan;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_wildcard_scope_grants_everything(): void
    {
        $key = ApiKey::factory()->create(['permissions' => ['*']]);

        $this->assertTrue($key->hasScope('documents:write'));
        $this->assertTrue($key->hasScope('products:read'));
    }

    public function test_empty_permissions_mean_full_access_and_catalogs_are_implicit(): void
    {
        $key = ApiKey::factory()->create(['permissions' => null]);
        $this->assertSame(['*'], $key->scopes());

        $limited = ApiKey::factory()->create(['permissions' => ['customers:read']]);
        $this->assertTrue($limited->hasScope('customers:read'));
        $this->assertTrue($limited->hasScope('catalogs:read'), 'catalogs:read es implícito');
        $this->assertFalse($limited->hasScope('documents:write'));
    }

    public function test_validity_depends_on_active_flag_and_expiry(): void
    {
        $this->assertTrue(ApiKey::factory()->create()->isValid());
        $this->assertFalse(ApiKey::factory()->inactive()->create()->isValid());
        $this->assertFalse(ApiKey::factory()->expired()->create()->isValid());
        $this->assertTrue(ApiKey::factory()->create(['expires_at' => now()->addDay()])->isValid());
    }

    public function test_effective_rate_limit_is_capped_by_the_plan(): void
    {
        $plan = Plan::factory()->create(['slug' => 'negocio', 'has_api_access' => true]);
        $tenant = Tenant::factory()->create(['current_plan_id' => $plan->id]);

        $key = ApiKey::factory()->create(['tenant_id' => $tenant->id, 'rate_limit_per_minute' => 500]);
        $this->assertSame(60, $key->effectiveRateLimit());

        $slow = ApiKey::factory()->create(['tenant_id' => $tenant->id, 'rate_limit_per_minute' => 10]);
        $this->assertSame(10, $slow->effectiveRateLimit());

        $enterprise = Plan::factory()->create(['slug' => 'enterprise', 'has_api_access' => true]);
        $tenant->update(['current_plan_id' => $enterprise->id]);
        $this->assertSame(300, $key->fresh()->effectiveRateLimit());

        $noPlan = Tenant::factory()->create(['current_plan_id' => null]);
        $orphan = ApiKey::factory()->create(['tenant_id' => $noPlan->id, 'rate_limit_per_minute' => 0]);
        $this->assertSame(ApiKey::DEFAULT_RATE_LIMIT, $orphan->effectiveRateLimit());
    }

    public function test_find_by_plain_key_and_rotation(): void
    {
        $plain = ApiKey::generatePlainKey();
        $this->assertSame(44, strlen($plain));
        $this->assertStringStartsWith('fec_', $plain);

        $key = ApiKey::factory()->withPlainKey($plain)->create();
        $this->assertSame(12, strlen($key->key_prefix));
        $this->assertTrue(ApiKey::findByPlainKey($plain)->is($key));
        $this->assertNull(ApiKey::findByPlainKey('fec_nope'));

        $newPlain = $key->rotate();
        $this->assertNotSame($plain, $newPlain);
        $this->assertNull(ApiKey::findByPlainKey($plain), 'la llave anterior deja de resolver');
        $this->assertTrue(ApiKey::findByPlainKey($newPlain)->is($key));
        $this->assertSame(ApiKey::prefixFrom($newPlain), $key->fresh()->key_prefix);
    }

    public function test_touch_usage_is_throttled_to_once_per_minute(): void
    {
        $key = ApiKey::factory()->create();

        $key->touchUsage('10.0.0.1');
        $first = $key->fresh()->last_used_at;
        $this->assertNotNull($first);
        $this->assertSame('10.0.0.1', $key->fresh()->last_used_ip);

        $this->travel(10)->seconds();
        $key->touchUsage('10.0.0.1');
        $this->assertTrue($key->fresh()->last_used_at->equalTo($first), 'no escribe de nuevo dentro del minuto');

        $this->travel(2)->minutes();
        $key->touchUsage('10.0.0.1');
        $this->assertTrue($key->fresh()->last_used_at->gt($first));
    }
}
