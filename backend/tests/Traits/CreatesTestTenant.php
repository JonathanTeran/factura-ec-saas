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
            // Precio determinista: el factory lo elegía al azar, lo que hacía
            // impredecible si un cambio de plan era upgrade o downgrade.
            'price_monthly' => 14.99,
            'price_yearly' => 149.99,
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

        // El checklist de emisión verifica que el archivo .p12 EXISTA en
        // storage (Company::hasSignatureFile), no solo la metadata del factory.
        \Illuminate\Support\Facades\Storage::fake();
        if ($this->company->signature_path) {
            \Illuminate\Support\Facades\Storage::put(
                $this->company->signature_path,
                'fake-p12-content'
            );
        }

        $this->branch = Branch::factory()->main()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
        ]);

        $this->emissionPoint = EmissionPoint::factory()->main()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
        ]);

        // Ciclo mensual determinista: el factory base lo elegía al azar, lo que
        // ahora hace flaky a los tests de límite de documentos (mensual vs anual).
        $this->subscription = Subscription::factory()->active()->monthly()->create([
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

        $document = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            ...$attrs,
        ]);

        // Con al menos una línea de detalle: la emisión pre-valida (SriPreValidator)
        // que el documento tenga items antes de enviarlo al SRI.
        \App\Models\SRI\DocumentItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'electronic_document_id' => $document->id,
            'product_id' => $this->createProduct()->id,
        ]);

        return $document;
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
