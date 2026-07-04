<?php

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class PlanFeatureGatingTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_inventory_blocked_when_plan_lacks_feature(): void
    {
        $this->tenant->update(['has_inventory' => false]);

        $this->getJson('/api/v1/inventory')
            ->assertStatus(403)
            ->assertJson(['error' => 'feature_not_available', 'feature' => 'inventory']);
    }

    public function test_inventory_allowed_when_plan_has_feature(): void
    {
        $this->tenant->update(['has_inventory' => true]);

        // 200 o al menos no 403 por feature (la ruta existe y pasa el gate)
        $this->getJson('/api/v1/inventory')->assertOk();
    }

    public function test_pos_blocked_when_plan_lacks_feature(): void
    {
        $this->tenant->update(['has_pos' => false]);

        $this->getJson('/api/v1/pos/sessions')
            ->assertStatus(403)
            ->assertJson(['feature' => 'pos']);
    }

    public function test_recurring_invoices_blocked_when_plan_lacks_feature(): void
    {
        $this->tenant->update(['has_recurring_invoices' => false]);

        $this->getJson('/api/v1/recurring-invoices')
            ->assertStatus(403)
            ->assertJson(['feature' => 'recurring_invoices']);
    }

    public function test_ats_report_available_on_all_plans(): void
    {
        // ATS está en todos los planes: no debe bloquearse por el gate de
        // reportes avanzados. Pasar el gate = NO recibir 403 feature_not_available.
        $this->tenant->update(['has_advanced_reports' => false]);

        $response = $this->getJson('/api/v1/reports/ats?year='.now()->year.'&month='.now()->month);

        $this->assertNotSame(403, $response->status(), 'ATS no debería bloquearse por plan.');
    }

    public function test_advanced_report_blocked_without_feature(): void
    {
        $this->tenant->update(['has_advanced_reports' => false]);

        $this->getJson('/api/v1/reports/comparison')
            ->assertStatus(403)
            ->assertJson(['feature' => 'advanced_reports']);
    }

    public function test_change_plan_syncs_tenant_features(): void
    {
        // Plan destino con POS/inventario y precio no superior (para que el
        // cambio se aplique de inmediato, sin requerir pago).
        $this->tenant->update(['has_pos' => false]);
        $target = Plan::factory()->create([
            'has_pos' => true,
            'has_inventory' => true,
            'price_monthly' => 4.99,
        ]);

        app(\App\Services\Billing\BillingService::class)
            ->changePlan($this->subscription, $target, 'monthly');

        $this->assertTrue($this->tenant->fresh()->has_pos);
        $this->assertTrue($this->tenant->fresh()->has_inventory);
    }

    public function test_document_limit_is_monthly_for_monthly_billing(): void
    {
        $this->subscription->update(['billing_cycle' => 'monthly']);
        $this->tenant->update(['max_documents_per_month' => 3]);

        $this->assertSame(3, $this->tenant->fresh()->effectiveDocumentLimit());
    }

    public function test_document_limit_is_annual_for_yearly_billing(): void
    {
        $this->subscription->update([
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonths(2),
        ]);
        $this->tenant->update(['max_documents_per_month' => 3]);

        // 3/mes * 12 = 36/año
        $this->assertSame(36, $this->tenant->fresh()->effectiveDocumentLimit());
    }

    public function test_document_limit_counts_within_period(): void
    {
        $this->subscription->update(['billing_cycle' => 'monthly']);
        $this->tenant->update(['max_documents_per_month' => 2]);

        $tenant = $this->tenant->fresh();
        $this->assertTrue($tenant->canIssueDocuments());

        // Crear 2 documentos este mes => alcanza el tope
        ElectronicDocument::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'created_at' => now(),
        ]);

        $this->assertFalse($this->tenant->fresh()->canIssueDocuments());
    }

    public function test_priority_queue_feature_routes_document_job(): void
    {
        $this->tenant->update(['has_priority_queue' => true]);

        $document = ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $job = new \App\Jobs\SRI\ProcessDocumentJob($document->fresh());
        $this->assertSame('sri-priority', $job->queue);
    }

    public function test_standard_plan_uses_default_sri_queue(): void
    {
        $this->tenant->update(['has_priority_queue' => false]);

        $document = ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $job = new \App\Jobs\SRI\ProcessDocumentJob($document->fresh());
        $this->assertSame('sri', $job->queue);
    }

    public function test_professional_plan_grants_premium_features(): void
    {
        $plan = Plan::where('slug', 'profesional')->first()
            ?? Plan::factory()->create([
                'has_priority_queue' => true,
                'has_bulk_operations' => true,
                'has_custom_roles' => true,
                'price_monthly' => 0,
            ]);

        $this->tenant->syncPlanLimits($plan);
        $tenant = $this->tenant->fresh();

        $this->assertTrue($tenant->has_priority_queue);
        $this->assertTrue($tenant->hasFeature('priority_queue'));
        $this->assertTrue($tenant->has_bulk_operations);
        $this->assertTrue($tenant->has_custom_roles);
    }

    public function test_every_toggled_feature_appears_in_web_list(): void
    {
        // Lo que el super admin activa debe reflejarse en la lista de la web.
        $plan = Plan::factory()->create([
            'features_json' => [],
            'has_bulk_operations' => true,
            'has_custom_roles' => true,
            'has_sso' => true,
            'has_priority_queue' => true,
        ]);

        $list = $plan->getFeaturesList();

        $this->assertContains('Emision y carga masiva', $list);
        $this->assertContains('Roles y permisos personalizados', $list);
        $this->assertContains('Inicio de sesion SSO/SAML', $list);
        $this->assertContains('Emision prioritaria al SRI', $list);
    }

    public function test_custom_features_json_overrides_web_list(): void
    {
        // Si el super admin define una lista propia, esa manda tal cual.
        $plan = Plan::factory()->create([
            'has_pos' => true,
            'has_inventory' => true,
            'features_json' => ['Todo incluido', 'Descuento de lanzamiento'],
        ]);

        $this->assertSame(
            ['Todo incluido', 'Descuento de lanzamiento'],
            $plan->getFeaturesList()
        );
    }
}
