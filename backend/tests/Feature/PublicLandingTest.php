<?php

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Services\Settings\PricingContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class PublicLandingTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    private const URL = '/api/v1/public/landing';

    private function makePlan(array $attrs = []): Plan
    {
        return Plan::factory()->create(array_merge([
            'is_active' => true,
            'price_monthly' => 7.99,
            'price_yearly' => 79.90,
            'sort_order' => 1,
        ], $attrs));
    }

    public function test_returns_active_paid_plans_in_order_without_auth(): void
    {
        $this->makePlan(['name' => 'Negocio', 'slug' => 'negocio', 'sort_order' => 2, 'is_featured' => true]);
        $this->makePlan(['name' => 'Emprendedor', 'slug' => 'emprendedor', 'price_monthly' => 2.99, 'price_yearly' => 29.90, 'sort_order' => 1, 'is_featured' => false]);
        $this->makePlan(['name' => 'Inactivo', 'slug' => 'inactivo', 'is_active' => false]);
        $this->makePlan(['name' => 'Gratis', 'slug' => 'gratis', 'price_monthly' => 0, 'price_yearly' => 0]);

        $res = $this->getJson(self::URL);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.plans')
            ->assertJsonPath('data.plans.0.slug', 'emprendedor')
            ->assertJsonPath('data.plans.1.slug', 'negocio')
            ->assertJsonPath('data.plans.1.is_featured', true)
            ->assertJsonPath('data.plans.0.price_monthly', 2.99)
            ->assertJsonPath('data.plans.0.currency', 'USD')
            ->assertJsonPath('data.plans.0.yearly_savings_percent', 17);

        $this->assertNotEmpty($res->json('data.plans.0.features_list'));
    }

    public function test_contact_sales_plan_is_listed_without_price(): void
    {
        $this->makePlan(['name' => 'Negocio', 'slug' => 'negocio', 'sort_order' => 2]);
        $this->makePlan([
            'name' => 'Enterprise', 'slug' => 'enterprise', 'sort_order' => 4,
            'price_monthly' => 49.99, 'price_yearly' => 499, 'is_contact_sales' => true,
        ]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonCount(2, 'data.plans')
            ->assertJsonPath('data.plans.1.slug', 'enterprise')
            ->assertJsonPath('data.plans.1.is_contact_sales', true)
            ->assertJsonPath('data.plans.1.price_monthly', 0)
            ->assertJsonPath('data.plans.1.price_yearly', 0)
            ->assertJsonPath('data.plans.0.is_contact_sales', false);

        // En autoservicio (panel/app) el plan a medida no se ofrece.
        $this->setUpTenantContext();
        $slugs = collect($this->getJson('/api/v1/subscription/plans')->json('data.plans'))->pluck('slug');
        $this->assertFalse($slugs->contains('enterprise'));
        $this->assertTrue($slugs->contains('negocio'));
    }

    public function test_pricing_content_falls_back_to_defaults_and_reflects_saved_values(): void
    {
        $this->makePlan();

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.pricing_content.eyebrow', 'Planes')
            ->assertJsonPath('data.pricing_content.badge_enabled', true);

        app(PricingContentSettings::class)->save(['title' => 'Título editado', 'badge_enabled' => false]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.pricing_content.title', 'Título editado')
            ->assertJsonPath('data.pricing_content.badge_enabled', false);
    }

    public function test_response_is_cached_and_invalidated_when_a_plan_is_saved(): void
    {
        $this->makePlan(['name' => 'Negocio', 'slug' => 'negocio']);
        $this->getJson(self::URL)->assertJsonPath('data.plans.0.name', 'Negocio');

        // Update por query builder: no dispara eventos, así que la caché sigue viva.
        Plan::query()->where('slug', 'negocio')->update(['name' => 'Renombrado']);
        $this->getJson(self::URL)->assertJsonPath('data.plans.0.name', 'Negocio');

        // Guardar un plan (evento saved) invalida la caché.
        $this->makePlan(['name' => 'Profesional', 'slug' => 'profesional', 'sort_order' => 2]);
        $res = $this->getJson(self::URL);
        $res->assertJsonCount(2, 'data.plans')
            ->assertJsonPath('data.plans.0.name', 'Renombrado');
    }

    public function test_is_rate_limited(): void
    {
        $this->makePlan();

        for ($i = 0; $i < 60; $i++) {
            $this->getJson(self::URL)->assertOk();
        }

        $this->getJson(self::URL)->assertStatus(429);
    }
}
