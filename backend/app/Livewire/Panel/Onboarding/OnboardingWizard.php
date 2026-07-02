<?php

namespace App\Livewire\Panel\Onboarding;

use App\Models\Billing\Plan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Tenant;
use App\Models\SRI\SequentialNumber;
use App\Services\SRI\RucLookupService;
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
    public string $email = '';
    public string $sri_password = '';
    public string $sri_environment = '1';
    public string $taxpayer_type = 'natural';
    public bool $obligated_accounting = false;
    public string $rimpe_type = 'none';
    public ?string $rucLookupStatus = null;
    public array $sriEstablishments = [];

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
    public bool $sriPasswordConfigured = false;
    public ?int $companyId = null;

    public function mount()
    {
        $tenant = auth()->user()->tenant;
        $this->loadExistingSetup($tenant);
        $this->refreshProgressFlags();

        // Pre-select current plan if tenant already has one
        if ($tenant->current_plan_id) {
            $this->selectedPlanId = $tenant->current_plan_id;
        }

        // Check if onboarding was already started
        $step = (int) ($tenant->settings['onboarding_step'] ?? 1);
        $this->currentStep = $step > 1
            ? min($step, $this->maxAccessibleStep())
            : 1;
    }

    // Consulta automática al escribir un RUC completo
    public function updatedRuc(): void
    {
        if (preg_match('/^[0-9]{13}$/', $this->ruc) === 1) {
            $this->applyRucLookup(silent: true);
        }
    }

    // Step 1: Autofill from the SRI public catastro
    public function lookupRuc()
    {
        $this->validate([
            'ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/'],
        ]);

        $this->applyRucLookup(silent: false);
    }

    private function applyRucLookup(bool $silent): void
    {
        $lookupService = app(RucLookupService::class);
        $taxpayer = $lookupService->lookup($this->ruc);

        if ($taxpayer === null) {
            if (! $silent) {
                $this->addError('ruc', 'No se pudo obtener la información del RUC desde el SRI. Verifica el número o ingresa los datos manualmente.');
            }
            return;
        }

        $this->business_name = $taxpayer['business_name'] ?: $this->business_name;
        $this->taxpayer_type = $taxpayer['taxpayer_type'];
        $this->obligated_accounting = $taxpayer['obligated_accounting'];
        $this->rimpe_type = match ($taxpayer['regime']) {
            'rimpe_emprendedor' => 'emprendedor',
            'rimpe_popular' => 'negocio_popular',
            default => 'none',
        };
        $this->rucLookupStatus = $taxpayer['status'];

        $this->sriEstablishments = $lookupService->establishments($this->ruc);
        $main = collect($this->sriEstablishments)->firstWhere('is_main', true);

        if ($main) {
            $this->trade_name = $this->trade_name ?: (string) ($main['trade_name'] ?? '');
            $this->address = $this->address ?: (string) ($main['address'] ?? '');

            // La matriz del SRI define el establecimiento principal (código y dirección)
            $this->branch_code = $main['code'];
            $this->branch_name = $main['trade_name'] ?: $this->branch_name;
            $this->branch_address = $main['address'] ?: $this->branch_address;
        }
    }

    // Step 1: Save company
    public function saveCompany()
    {
        $rules = [
            'ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/'],
            'business_name' => ['required', 'string', 'max:300'],
            'address' => ['required', 'string', 'max:300'],
            'email' => ['required', 'email', 'max:255'],
            'sri_environment' => ['required', 'in:1,2'],
            'sri_password' => [$this->sriPasswordConfigured ? 'nullable' : 'required', 'string', 'max:255'],
        ];

        $this->validate($rules);

        $tenant = auth()->user()->tenant;

        $company = $this->resolveCompany() ?? new Company(['tenant_id' => $tenant->id]);
        $company->fill([
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name ?: $this->business_name,
            'address' => $this->address,
            'email' => $this->email,
            'sri_environment' => $this->sri_environment,
            'taxpayer_type' => $this->taxpayer_type,
            'obligated_accounting' => $this->obligated_accounting,
            'rimpe_type' => $this->rimpe_type,
            'is_active' => true,
        ]);

        if (filled($this->sri_password)) {
            $company->setSriPassword($this->sri_password);
        }

        $company->save();

        $this->companyId = $company->id;
        $this->reset('sri_password');
        $this->refreshProgressFlags();
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
            $this->refreshProgressFlags();
            $this->goToStep(3);
        } catch (\Exception $e) {
            $this->addError('certificate', $e->getMessage());
        }
    }

    public function skipCertificate()
    {
        $this->addError('onboarding', 'La firma electronica es obligatoria para continuar.');
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

        $branch = Branch::firstOrNew([
            'tenant_id' => $tenant->id,
            'company_id' => $this->companyId,
            'is_main' => true,
        ]);

        $branch->fill([
            'code' => $this->branch_code,
            'name' => $this->branch_name,
            'address' => $this->branch_address,
            'is_active' => true,
        ]);
        $branch->save();

        $ep = EmissionPoint::updateOrCreate([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'code' => $this->ep_code,
        ], [
            'name' => 'Punto de Emision 1',
            'is_active' => true,
        ]);

        $this->initializeSequentials($tenant->id, $ep->id);

        // Importa las demás sucursales abiertas registradas en el SRI
        foreach ($this->sriEstablishments as $sriBranch) {
            if (! ($sriBranch['is_open'] ?? false) || $sriBranch['code'] === $this->branch_code) {
                continue;
            }

            $extraBranch = Branch::firstOrCreate([
                'tenant_id' => $tenant->id,
                'company_id' => $this->companyId,
                'code' => $sriBranch['code'],
            ], [
                'name' => $sriBranch['trade_name'] ?: 'Establecimiento ' . $sriBranch['code'],
                'address' => $sriBranch['address'] ?? '',
                'is_main' => false,
                'is_active' => true,
            ]);

            $extraEp = EmissionPoint::firstOrCreate([
                'tenant_id' => $tenant->id,
                'branch_id' => $extraBranch->id,
                'code' => '001',
            ], [
                'name' => 'Punto de Emision 1',
                'is_active' => true,
            ]);

            $this->initializeSequentials($tenant->id, $extraEp->id);
        }

        $this->refreshProgressFlags();
        $this->goToStep(4);
    }

    private function initializeSequentials(int $tenantId, int $emissionPointId): void
    {
        foreach (['01', '03', '04', '05', '06', '07'] as $docType) {
            SequentialNumber::firstOrCreate([
                'tenant_id' => $tenantId,
                'emission_point_id' => $emissionPointId,
                'document_type' => $docType,
            ], [
                'current_number' => 0,
            ]);
        }
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
        $plan = Plan::findOrFail($this->selectedPlanId);
        $tenant->syncPlanLimits($plan);
        $tenant->refresh();

        $this->goToStep(6);
    }

    public function skipPlan()
    {
        $this->addError('onboarding', 'Debes seleccionar un plan antes de continuar.');
    }

    // Step 6: Complete
    public function completeOnboarding()
    {
        if (!$this->canCompleteOnboarding) {
            $this->addError('onboarding', 'Completa toda la configuracion obligatoria antes de continuar.');
            $this->goToStep($this->firstIncompleteStep());

            return;
        }

        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toIso8601String();
        $tenant->update(['settings' => $settings]);

        // Contabilidad básica de serie: plan de cuentas, periodos y asientos automáticos
        if ($company = $this->resolveCompany()) {
            app(\App\Services\Accounting\BasicAccountingActivator::class)
                ->activateQuietly($company);
        }

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
        $this->currentStep = max(1, min($step, $this->maxAccessibleStep()));

        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['onboarding_step'] = $this->currentStep;
        $tenant->update(['settings' => $settings]);
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->goToStep($this->currentStep - 1);
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

    public function getRequiredSetupItemsProperty(): array
    {
        $company = $this->resolveCompany();
        $checklist = $company?->emissionReadinessChecklist() ?? [
            'basic_data' => false,
            'sri_password' => false,
            'digital_signature' => false,
            'establishments' => false,
        ];

        return [
            [
                'label' => 'Datos fiscales y correo del emisor',
                'description' => 'RUC, razon social, direccion y correo configurados.',
                'ready' => (bool) ($checklist['basic_data'] ?? false),
            ],
            [
                'label' => 'Clave del SRI',
                'description' => 'Credenciales guardadas para conexion con SRI.',
                'ready' => (bool) ($checklist['sri_password'] ?? false),
            ],
            [
                'label' => 'Firma electronica',
                'description' => 'Certificado .p12 vigente cargado en la cuenta.',
                'ready' => (bool) ($checklist['digital_signature'] ?? false),
            ],
            [
                'label' => 'Establecimiento y punto de emision',
                'description' => 'Al menos una sucursal activa con punto de emision.',
                'ready' => (bool) ($checklist['establishments'] ?? false),
            ],
            [
                'label' => 'Plan activo',
                'description' => 'El tenant debe tener un plan seleccionado.',
                'ready' => $this->hasSelectedPlan(),
            ],
        ];
    }

    public function getCanCompleteOnboardingProperty(): bool
    {
        $company = $this->resolveCompany();

        if (!$company) {
            return false;
        }

        return collect($company->emissionReadinessChecklist())->every(
            fn (bool $isReady): bool => $isReady
        ) && $this->hasSelectedPlan();
    }

    private function loadExistingSetup(Tenant $tenant): void
    {
        $company = Company::where('tenant_id', $tenant->id)->first();

        if (!$company) {
            return;
        }

        $this->companyId = $company->id;
        $this->ruc = $company->ruc;
        $this->business_name = $company->business_name;
        $this->trade_name = $company->trade_name ?? '';
        $this->address = $company->address;
        $this->email = $company->email ?? '';
        $this->sri_environment = (string) $company->sri_environment;
        $this->taxpayer_type = $company->taxpayer_type ?? 'natural';
        $this->obligated_accounting = (bool) $company->obligated_accounting;

        $mainBranch = $company->branches()
            ->where('is_main', true)
            ->with('emissionPoints')
            ->first();

        if ($mainBranch) {
            $this->branch_name = $mainBranch->name;
            $this->branch_address = $mainBranch->address;
            $this->branch_code = $mainBranch->code;
            $this->ep_code = (string) ($mainBranch->emissionPoints->first()?->code ?? $this->ep_code);
        }
    }

    private function refreshProgressFlags(): void
    {
        $company = $this->resolveCompany();

        $this->companyCreated = (bool) $company;
        $this->companyId = $company?->id;
        $this->sriPasswordConfigured = (bool) ($company?->hasSriPassword() ?? false);
        $this->certificateUploaded = (bool) ($company?->hasValidSignature() ?? false);
        $this->branchCreated = (bool) ($company?->hasOperationalSetup() ?? false);
    }

    private function resolveCompany(): ?Company
    {
        if ($this->companyId) {
            return Company::find($this->companyId);
        }

        return auth()->user()->tenant->companies()->first();
    }

    private function hasSelectedPlan(): bool
    {
        return filled($this->selectedPlanId);
    }

    private function maxAccessibleStep(): int
    {
        $company = $this->resolveCompany();

        if (!$company || !$company->hasBasicFiscalData() || !$company->hasSriPassword()) {
            return 1;
        }

        if (!$company->hasValidSignature()) {
            return 2;
        }

        if (!$company->hasOperationalSetup()) {
            return 3;
        }

        if (!$this->hasSelectedPlan()) {
            return 5;
        }

        return 6;
    }

    private function firstIncompleteStep(): int
    {
        return $this->maxAccessibleStep();
    }

    public function render()
    {
        return view('livewire.panel.onboarding.wizard')
            ->layout('layouts.tenant');
    }
}
