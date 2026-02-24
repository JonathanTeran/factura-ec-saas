<?php

namespace App\Livewire\Panel\Onboarding;

use App\Models\Billing\Plan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\SRI\SequentialNumber;
use App\Services\SRI\SignatureManager;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class OnboardingWizard extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public int $totalSteps = 6;

    // Step 1: Company info
    public string $ruc = '';
    public string $business_name = '';
    public string $trade_name = '';
    public string $address = '';
    public string $sri_environment = '1';
    public string $taxpayer_type = 'natural';
    public bool $obligated_accounting = false;

    // Step 2: Certificate
    public $certificate;
    public string $certificate_password = '';
    public ?array $certificateInfo = null;

    // Step 3: Branch + Emission Point
    public string $branch_name = 'Matriz';
    public string $branch_address = '';
    public string $branch_code = '001';
    public string $ep_code = '001';

    // Step 4: First customer (optional)
    public string $customer_identification_type = '05';
    public string $customer_identification = '';
    public string $customer_name = '';
    public string $customer_email = '';

    // Step 5: Plan selection
    public ?int $selectedPlanId = null;
    public string $billingCycle = 'monthly';

    // Computed
    public bool $companyCreated = false;
    public bool $certificateUploaded = false;
    public bool $branchCreated = false;
    public ?int $companyId = null;

    public function mount()
    {
        $tenant = auth()->user()->tenant;
        // Check if already has a company
        $company = Company::where('tenant_id', $tenant->id)->first();
        if ($company) {
            $this->companyId = $company->id;
            $this->companyCreated = true;
            $this->ruc = $company->ruc;
            $this->business_name = $company->business_name;

            if ($company->hasValidSignature()) {
                $this->certificateUploaded = true;
            }

            if ($company->branches()->exists()) {
                $this->branchCreated = true;
            }
        }

        // Pre-select current plan if tenant already has one
        if ($tenant->current_plan_id) {
            $this->selectedPlanId = $tenant->current_plan_id;
        }

        // Check if onboarding was already started
        $step = $tenant->settings['onboarding_step'] ?? 1;
        $this->currentStep = min($step, $this->totalSteps);
    }

    // Step 1: Save company
    public function saveCompany()
    {
        $this->validate([
            'ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/'],
            'business_name' => ['required', 'string', 'max:300'],
            'address' => ['required', 'string', 'max:300'],
            'sri_environment' => ['required', 'in:1,2'],
        ]);

        $tenant = auth()->user()->tenant;

        $company = Company::updateOrCreate(
            ['tenant_id' => $tenant->id, 'ruc' => $this->ruc],
            [
                'business_name' => $this->business_name,
                'trade_name' => $this->trade_name ?: $this->business_name,
                'address' => $this->address,
                'sri_environment' => $this->sri_environment,
                'taxpayer_type' => $this->taxpayer_type,
                'obligated_accounting' => $this->obligated_accounting,
                'is_active' => true,
            ]
        );

        $this->companyId = $company->id;
        $this->companyCreated = true;
        $this->goToStep(2);
    }

    // Step 2: Upload certificate
    public function saveCertificate()
    {
        $this->validate([
            'certificate' => ['required', 'file', 'max:5120'],
            'certificate_password' => ['required', 'string'],
        ]);

        $company = Company::find($this->companyId);
        $manager = app(SignatureManager::class);

        try {
            $info = $manager->store($company, $this->certificate, $this->certificate_password);
            $this->certificateInfo = $info;
            $this->certificateUploaded = true;
            $this->goToStep(3);
        } catch (\Exception $e) {
            $this->addError('certificate', $e->getMessage());
        }
    }

    public function skipCertificate()
    {
        $this->goToStep(3);
    }

    // Step 3: Create branch + emission point
    public function saveBranch()
    {
        $this->validate([
            'branch_name' => ['required', 'string', 'max:300'],
            'branch_address' => ['required', 'string', 'max:300'],
            'branch_code' => ['required', 'string', 'size:3', 'regex:/^[0-9]+$/'],
            'ep_code' => ['required', 'string', 'size:3', 'regex:/^[0-9]+$/'],
        ]);

        $tenant = auth()->user()->tenant;

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'company_id' => $this->companyId,
            'code' => $this->branch_code,
            'name' => $this->branch_name,
            'address' => $this->branch_address,
            'is_main' => true,
            'is_active' => true,
        ]);

        $ep = EmissionPoint::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'code' => $this->ep_code,
            'name' => 'Punto de Emision 1',
            'is_active' => true,
        ]);

        // Initialize sequential numbers for all document types
        foreach (['01', '04', '05', '06', '07'] as $docType) {
            SequentialNumber::firstOrCreate([
                'tenant_id' => $tenant->id,
                'emission_point_id' => $ep->id,
                'document_type' => $docType,
            ], [
                'current_number' => 0,
            ]);
        }

        $this->branchCreated = true;
        $this->goToStep(4);
    }

    // Step 4: Create first customer (optional)
    public function saveCustomer()
    {
        $this->validate([
            'customer_identification' => ['required', 'string'],
            'customer_name' => ['required', 'string', 'max:300'],
            'customer_email' => ['nullable', 'email'],
        ]);

        $tenant = auth()->user()->tenant;

        Customer::create([
            'tenant_id' => $tenant->id,
            'identification_type' => $this->customer_identification_type,
            'identification_number' => $this->customer_identification,
            'name' => $this->customer_name,
            'email' => $this->customer_email ?: null,
        ]);

        $this->goToStep(5);
    }

    public function skipCustomer()
    {
        $this->goToStep(5);
    }

    // Step 5: Select plan
    public function selectPlan(int $planId)
    {
        $this->selectedPlanId = $planId;
    }

    public function savePlan()
    {
        $this->validate([
            'selectedPlanId' => ['required', 'exists:plans,id'],
            'billingCycle' => ['required', 'in:monthly,yearly'],
        ]);

        $tenant = auth()->user()->tenant;
        $tenant->update(['current_plan_id' => $this->selectedPlanId]);

        $this->goToStep(6);
    }

    public function skipPlan()
    {
        $this->goToStep(6);
    }

    // Step 6: Complete
    public function completeOnboarding()
    {
        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toIso8601String();
        $tenant->update(['settings' => $settings]);

        // If a paid plan was selected, redirect to billing settings to complete payment
        if ($this->selectedPlanId) {
            $plan = Plan::find($this->selectedPlanId);
            if ($plan && !$plan->isFree()) {
                return redirect()->route('panel.settings.billing', [
                    'plan' => $this->selectedPlanId,
                    'cycle' => $this->billingCycle,
                ]);
            }
        }

        return redirect()->route('panel.dashboard');
    }

    public function goToStep(int $step)
    {
        $this->currentStep = $step;

        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['onboarding_step'] = $step;
        $tenant->update(['settings' => $settings]);
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function getPlansProperty(): Collection
    {
        return Plan::active()->ordered()->get();
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        if ($this->selectedPlanId) {
            return Plan::find($this->selectedPlanId);
        }

        return null;
    }

    public function getSelectedPlanPriceProperty(): float
    {
        $plan = $this->selectedPlan;
        if (!$plan) {
            return 0;
        }

        if ($this->billingCycle === 'yearly' && $plan->price_yearly) {
            return (float) $plan->price_yearly;
        }

        return (float) $plan->price_monthly;
    }

    public function render()
    {
        return view('livewire.panel.onboarding.wizard')
            ->layout('layouts.tenant');
    }
}
