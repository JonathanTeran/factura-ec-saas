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
        $this->tenant->update(['settings' => ['onboarding_step' => 2]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 2)
            ->call('skipCertificate')
            ->assertSet('currentStep', 3);
    }

    public function test_onboarding_wizard_skip_customer(): void
    {
        // Start at step 4 (customer step)
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
        $this->tenant->update(['settings' => ['onboarding_step' => 5]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->assertSet('currentStep', 5)
            ->call('skipPlan')
            ->assertSet('currentStep', 6);
    }

    public function test_onboarding_completion_marks_tenant(): void
    {
        // Start at step 6 (completion step)
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
        $this->tenant->update(['settings' => ['onboarding_step' => 2]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('skipCertificate');

        // Verify the step was saved in tenant settings
        $this->tenant->refresh();
        $this->assertEquals(3, $this->tenant->settings['onboarding_step']);
    }

    public function test_onboarding_wizard_resumes_from_saved_step(): void
    {
        // Simulate a tenant that previously reached step 4
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
            ->call('saveCompany')
            ->assertHasErrors(['ruc', 'business_name', 'address']);
    }

    public function test_onboarding_wizard_select_plan(): void
    {
        $this->tenant->update(['settings' => ['onboarding_step' => 5]]);

        $this->actingAs($this->user);

        Livewire::test(OnboardingWizard::class)
            ->call('selectPlan', $this->plan->id)
            ->assertSet('selectedPlanId', $this->plan->id);
    }
}
