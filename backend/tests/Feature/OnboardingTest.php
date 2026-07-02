<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckOnboarding;
use App\Livewire\Panel\Onboarding\OnboardingWizard;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class OnboardingTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    // ==================== Middleware Tests ====================

    public function test_onboarding_middleware_redirects_incomplete_tenant(): void
    {
        // Ensure onboarding is NOT completed
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/panel', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/panel', []);
            $route->name('panel.dashboard');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('onboarding', $response->headers->get('Location'));
    }

    public function test_onboarding_middleware_allows_completed_tenant(): void
    {
        // Mark onboarding as completed
        $this->tenant->update(['settings' => [
            'onboarding_completed' => true,
            'onboarding_completed_at' => now()->toIso8601String(),
        ]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/panel', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/panel', []);
            $route->name('panel.dashboard');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_allows_onboarding_route_for_incomplete_tenant(): void
    {
        // Onboarding NOT completed
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/panel/onboarding', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/panel/onboarding', []);
            $route->name('panel.onboarding');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Should pass through without redirect when on onboarding route
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_allows_api_routes(): void
    {
        // Onboarding NOT completed
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/api/v1/customers', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/api/v1/customers', []);
            $route->name('api.customers.index');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // API routes should pass through
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_allows_logout_route(): void
    {
        // Onboarding NOT completed
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/logout', 'POST');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('POST', '/logout', []);
            $route->name('logout');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Logout should pass through
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_skips_for_super_admin(): void
    {
        // Create a super admin user
        $admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Onboarding NOT completed
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/panel', 'GET');
        $request->setUserResolver(fn () => $admin);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/panel', []);
            $route->name('panel.dashboard');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Super admin should pass through even without onboarding
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_passes_when_no_user(): void
    {
        $middleware = new CheckOnboarding();

        $request = Request::create('/panel', 'GET');
        $request->setUserResolver(fn () => null);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // No user should pass through (let auth middleware handle it)
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_onboarding_middleware_passes_when_settings_empty(): void
    {
        // Empty settings means onboarding NOT completed
        $this->tenant->update(['settings' => []]);

        $middleware = new CheckOnboarding();

        $request = Request::create('/panel', 'GET');
        $request->setUserResolver(fn () => $this->user);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/panel', []);
            $route->name('panel.dashboard');
            return $route;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Should redirect when settings are empty
        $this->assertEquals(302, $response->getStatusCode());
    }

    // ==================== Livewire Wizard Tests ====================

    public function test_onboarding_wizard_page_loads(): void
    {
        // Mark onboarding as incomplete so the wizard is accessible
        $this->tenant->update(['settings' => ['onboarding_completed' => false]]);

        $this->actingAs($this->user);

        $response = $this->get(route('panel.onboarding'));

        $response->assertStatus(200);
    }

    public function test_onboarding_wizard_starts_at_step_one(): void
    {
        $this->tenant->update(['settings' => []]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSet('totalSteps', 6);
    }

    public function test_onboarding_wizard_step_navigation_previous(): void
    {
        // Start at step 3
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 3]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 3)
            ->call('previousStep')
            ->assertSet('currentStep', 2);
    }

    public function test_onboarding_wizard_previous_step_does_not_go_below_one(): void
    {
        $this->tenant->update(['settings' => ['onboarding_step' => 1]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 1)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    public function test_onboarding_wizard_skip_certificate(): void
    {
        // Start at step 2 (certificate step)
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 2]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 2)
            ->call('skipCertificate')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['onboarding']);
    }

    public function test_onboarding_wizard_skip_customer(): void
    {
        // Start at step 4 (customer step)
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 4]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 4)
            ->call('skipCustomer')
            ->assertSet('currentStep', 5);
    }

    public function test_onboarding_wizard_skip_plan(): void
    {
        // Start at step 5 (plan selection step)
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 5]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 5)
            ->call('skipPlan')
            ->assertSet('currentStep', 5)
            ->assertHasErrors(['onboarding']);
    }

    public function test_onboarding_completion_marks_tenant(): void
    {
        // Start at step 6 (completion step)
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 6]]);

        // Ensure the plan is free so we redirect to dashboard (not billing)
        $this->plan->update(['price_monthly' => 0, 'price_yearly' => 0]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 6)
            ->call('completeOnboarding')
            ->assertRedirect(route('panel.dashboard'));

        // Verify tenant settings were updated
        $this->tenant->refresh();
        $settings = $this->tenant->settings;

        $this->assertTrue($settings['onboarding_completed']);
        $this->assertArrayHasKey('onboarding_completed_at', $settings);
    }

    public function test_onboarding_completion_redirects_to_billing_for_paid_plan(): void
    {
        // Start at step 6 with a paid plan selected
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update([
            'settings' => ['onboarding_step' => 6],
            'current_plan_id' => $this->plan->id,
        ]);

        // Ensure the plan is paid
        $this->plan->update(['price_monthly' => 19.99, 'price_yearly' => 199.99]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('selectedPlanId', $this->plan->id)
            ->set('billingCycle', 'monthly')
            ->call('completeOnboarding')
            ->assertRedirect();

        // Verify tenant settings were updated regardless of redirect
        $this->tenant->refresh();
        $this->assertTrue($this->tenant->settings['onboarding_completed']);
    }

    public function test_onboarding_wizard_saves_step_in_tenant_settings(): void
    {
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 4]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('skipCustomer');

        // Verify the step was saved in tenant settings
        $this->tenant->refresh();
        $this->assertEquals(5, $this->tenant->settings['onboarding_step']);
    }

    public function test_onboarding_wizard_resumes_from_saved_step(): void
    {
        // Simulate a tenant that previously reached step 4
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 4]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 4);
    }

    public function test_onboarding_wizard_save_company_validates_ruc(): void
    {
        $this->tenant->update(['settings' => []]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('ruc', '123') // Invalid: must be 13 digits
            ->set('business_name', 'Test Company')
            ->set('address', 'Test Address')
            ->set('email', 'test@example.com')
            ->set('sri_password', 'test-sri-password')
            ->set('sri_environment', '1')
            ->call('saveCompany')
            ->assertHasErrors(['ruc']);
    }

    public function test_onboarding_wizard_save_company_requires_fields(): void
    {
        $this->tenant->update(['settings' => []]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('ruc', '')
            ->set('business_name', '')
            ->set('address', '')
            ->set('email', '')
            ->set('sri_password', '')
            ->call('saveCompany')
            ->assertHasErrors(['ruc', 'business_name', 'address', 'email', 'sri_password']);
    }

    public function test_onboarding_wizard_select_plan(): void
    {
        $this->company->setSriPassword('test-sri-password');
        $this->company->save();
        $this->tenant->update(['settings' => ['onboarding_step' => 5]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('selectPlan', $this->plan->id)
            ->assertSet('selectedPlanId', $this->plan->id);
    }

    public function test_onboarding_completion_requires_all_operational_configuration(): void
    {
        $this->tenant->update(['settings' => ['onboarding_step' => 6]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('completeOnboarding')
            ->assertSet('currentStep', 1)
            ->assertHasErrors(['onboarding']);

        $this->tenant->refresh();
        $this->assertFalse((bool) ($this->tenant->settings['onboarding_completed'] ?? false));
    }

    // ==================== SRI RUC Lookup Tests ====================

    private function fakeSriCatastro(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*ConsolidadoContribuyente*' => \Illuminate\Support\Facades\Http::response([[
                'numeroRuc' => '1207481803001',
                'razonSocial' => 'TERAN TRIANA JONATHAN EDUARDO',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'actividadEconomicaPrincipal' => 'DESARROLLO DE SOFTWARE',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'RIMPE',
                'categoria' => 'EMPRENDEDOR',
                'obligadoLlevarContabilidad' => 'SI',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ]]),
            '*Establecimiento*' => \Illuminate\Support\Facades\Http::response([[
                'nombreFantasiaComercial' => 'MI NEGOCIO',
                'tipoEstablecimiento' => 'MAT',
                'direccionCompleta' => 'GUAYAS / GUAYAQUIL / XIMENA / 50C SE 14',
                'estado' => 'ABIERTO',
                'numeroEstablecimiento' => '002',
                'matriz' => 'SI',
            ]]),
        ]);
    }

    public function test_lookup_ruc_autofills_company_fields_from_sri(): void
    {
        $this->fakeSriCatastro();
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('ruc', '1207481803001')
            ->set('business_name', '')
            ->set('trade_name', '')
            ->set('address', '')
            ->call('lookupRuc')
            ->assertHasNoErrors()
            ->assertSet('business_name', 'TERAN TRIANA JONATHAN EDUARDO')
            ->assertSet('taxpayer_type', 'natural')
            ->assertSet('obligated_accounting', true)
            ->assertSet('trade_name', 'MI NEGOCIO')
            ->assertSet('address', 'GUAYAS / GUAYAQUIL / XIMENA / 50C SE 14');
    }

    public function test_lookup_ruc_does_not_overwrite_filled_fields(): void
    {
        $this->fakeSriCatastro();
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('ruc', '1207481803001')
            ->set('business_name', '')
            ->set('trade_name', 'NOMBRE PROPIO')
            ->set('address', 'MI DIRECCION MANUAL')
            ->call('lookupRuc')
            ->assertSet('business_name', 'TERAN TRIANA JONATHAN EDUARDO')
            ->assertSet('trade_name', 'NOMBRE PROPIO')
            ->assertSet('address', 'MI DIRECCION MANUAL');
    }

    public function test_lookup_ruc_triggers_automatically_when_13_digits_entered(): void
    {
        $this->fakeSriCatastro();
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('business_name', '')
            ->set('ruc', '1207481803001')
            ->assertSet('business_name', 'TERAN TRIANA JONATHAN EDUARDO');
    }

    public function test_lookup_ruc_prefills_branch_from_sri_matriz(): void
    {
        $this->fakeSriCatastro();
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('branch_address', '')
            ->set('ruc', '1207481803001')
            ->call('lookupRuc')
            ->assertSet('branch_code', '002')
            ->assertSet('branch_address', 'GUAYAS / GUAYAQUIL / XIMENA / 50C SE 14')
            ->assertSet('branch_name', 'MI NEGOCIO');
    }

    public function test_save_branch_imports_additional_open_sri_establishments(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*ConsolidadoContribuyente*' => \Illuminate\Support\Facades\Http::response([[
                'numeroRuc' => '1207481803001',
                'razonSocial' => 'TERAN TRIANA JONATHAN EDUARDO',
                'estadoContribuyenteRuc' => 'ACTIVO',
                'tipoContribuyente' => 'PERSONA NATURAL',
                'regimen' => 'GENERAL',
                'obligadoLlevarContabilidad' => 'NO',
                'agenteRetencion' => 'NO',
                'contribuyenteEspecial' => 'NO',
            ]]),
            '*Establecimiento*' => \Illuminate\Support\Facades\Http::response([
                [
                    'nombreFantasiaComercial' => 'SUCURSAL NORTE',
                    'direccionCompleta' => 'PICHINCHA / QUITO / NORTE',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => '003',
                    'matriz' => 'NO',
                ],
                [
                    'nombreFantasiaComercial' => 'CERRADA',
                    'direccionCompleta' => 'LOS RIOS / VINCES',
                    'estado' => 'CERRADO',
                    'numeroEstablecimiento' => '001',
                    'matriz' => 'NO',
                ],
                [
                    'nombreFantasiaComercial' => null,
                    'direccionCompleta' => 'GUAYAS / GUAYAQUIL / XIMENA',
                    'estado' => 'ABIERTO',
                    'numeroEstablecimiento' => '002',
                    'matriz' => 'SI',
                ],
            ]),
        ]);
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('lookupRuc')
            ->call('saveBranch')
            ->assertHasNoErrors();

        // La matriz del SRI (002) es la sucursal principal
        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '002',
            'is_main' => true,
        ]);

        // La sucursal abierta adicional (003) se importa con su punto de emisión
        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '003',
            'name' => 'SUCURSAL NORTE',
            'is_main' => false,
        ]);
        $imported = \App\Models\Tenant\Branch::where('code', '003')->first();
        $this->assertNotNull($imported);
        $this->assertCount(1, $imported->emissionPoints);

        // La cerrada (001) no se importa
        $this->assertDatabaseMissing('branches', [
            'tenant_id' => $this->tenant->id,
            'code' => '001',
        ]);
    }

    public function test_lookup_ruc_shows_error_when_not_found(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'srienlinea.sri.gob.ec/*' => \Illuminate\Support\Facades\Http::response([]),
        ]);
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('ruc', '9999999999001')
            ->call('lookupRuc')
            ->assertHasErrors(['ruc']);
    }

    // ==================== Sequential Initialization ====================

    public function test_save_branch_initializes_all_six_document_type_sequentials(): void
    {
        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->set('branch_name', 'Matriz')
            ->set('branch_address', 'Guayaquil')
            ->set('branch_code', '001')
            ->set('ep_code', '001')
            ->call('saveBranch')
            ->assertHasNoErrors();

        foreach (['01', '03', '04', '05', '06', '07'] as $docType) {
            $this->assertDatabaseHas('sequential_numbers', [
                'tenant_id' => $this->tenant->id,
                'document_type' => $docType,
            ]);
        }
    }
}
