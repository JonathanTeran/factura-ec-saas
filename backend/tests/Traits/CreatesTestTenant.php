<?php

namespace Tests\Traits;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesTestTenant
{
    protected Tenant $tenant;
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected EmissionPoint $emissionPoint;
    protected Plan $plan;
    protected Subscription $subscription;

    protected function setUpTenantContext(): void
    {
        $this->plan = Plan::factory()->create([
            'max_documents_per_month' => 100,
            'max_users' => 10,
            'max_companies' => 3,
        ]);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'current_plan_id' => $this->plan->id,
            'max_documents_per_month' => 100,
            'max_users' => 10,
            'max_companies' => 3,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'tenant_owner',
            'is_active' => true,
        ]);

        // Set owner on tenant so BillingService notifications work
        $this->tenant->update(['owner_id' => $this->user->id]);

        $this->company = Company::factory()->withSignature()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->branch = Branch::factory()->main()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
        ]);

        $this->emissionPoint = EmissionPoint::factory()->main()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->subscription = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        config(['app.tenant_id' => $this->tenant->id]);

        Sanctum::actingAs($this->user);
    }

    protected function createCustomer(array $attrs = []): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            ...$attrs,
        ]);
    }

    protected function createProduct(array $attrs = []): Product
    {
        return Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            ...$attrs,
        ]);
    }

    protected function createDocument(array $attrs = []): ElectronicDocument
    {
        $customer = $this->createCustomer();

        return ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            ...$attrs,
        ]);
    }

    protected function createSecondTenant(): array
    {
        $tenant2 = Tenant::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'role' => 'tenant_owner',
            'is_active' => true,
        ]);

        return ['tenant' => $tenant2, 'user' => $user2];
    }
}
