<?php

namespace App\Livewire\Panel\Settings;

use App\Enums\DocumentType;
use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\EmissionPoint;
use App\Services\SRI\SignatureManager;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompanySettings extends Component
{
    use WithFileUploads;

    public ?Company $company = null;

    // Datos de empresa / emisor
    public string $ruc = '';
    public string $business_name = '';
    public string $trade_name = '';
    public string $address = '';
    public string $taxpayer_type = 'natural';
    public string $tax_regime = 'general';
    public bool $special_taxpayer = false;
    public string $special_taxpayer_number = '';
    public string $retention_agent_number = '';
    public bool $accounting_required = false;
    public bool $artisan_qualified = false;
    public string $artisan_qualification_number = '';
    public bool $is_transporter = false;
    public string $environment = '1';
    public string $sri_password = '';
    public string $email = '';
    public string $phone = '';

    // Logo
    public $logo;

    // Certificado
    public $certificate;
    public string $certificate_password = '';

    // WhatsApp
    public bool $whatsapp_enabled = false;
    public bool $whatsapp_auto_send = false;
    public string $whatsapp_phone = '';

    // Secuenciales
    public bool $showSequentialModal = false;
    public ?int $editingEmissionPointId = null;
    public string $editingEmissionPointName = '';
    public array $sequentialNumbers = [];

    // Sucursales
    public array $branches = [];
    public bool $showBranchModal = false;
    public ?int $editingBranchId = null;
    public string $branch_code = '';
    public string $branch_name = '';
    public string $branch_address = '';
    public bool $branch_is_main = false;

    public function mount(): void
    {
        $tenant = auth()->user()->tenant;
        $this->company = $tenant->companies()->first();

        if ($this->company) {
            $this->loadCompanyData();
            $this->loadBranches();
            $this->loadWhatsAppSettings();
        }
    }

    private function loadCompanyData(): void
    {
        $settings = $this->company->settings ?? [];

        $this->ruc = $this->company->ruc;
        $this->business_name = $this->company->business_name;
        $this->trade_name = $this->company->trade_name ?? '';
        $this->address = $this->company->address;
        $this->taxpayer_type = $this->company->taxpayer_type ?? 'natural';
        $this->tax_regime = $this->resolveTaxRegime(
            $this->company->rimpe_type ?? 'none',
            (bool) ($settings['is_simplified_society'] ?? false),
        );
        $this->special_taxpayer = (bool) $this->company->special_taxpayer;
        $this->special_taxpayer_number = $this->company->special_taxpayer_number ?? '';
        $this->retention_agent_number = $this->company->retention_agent_number ?? '';
        $this->accounting_required = (bool) $this->company->obligated_accounting;
        $this->artisan_qualified = (bool) ($settings['artisan_qualified'] ?? false);
        $this->artisan_qualification_number = $settings['artisan_qualification_number'] ?? '';
        $this->is_transporter = (bool) ($settings['is_transporter'] ?? false);
        $this->environment = (string) $this->company->sri_environment;
        $this->email = $this->company->email ?? '';
        $this->phone = $this->company->phone ?? '';
    }

    private function loadBranches(): void
    {
        if (!$this->company) {
            $this->branches = [];
            return;
        }

        $this->branches = $this->company->branches()
            ->with('emissionPoints')
            ->get()
            ->toArray();
    }

    private function loadWhatsAppSettings(): void
    {
        $settings = $this->company->settings ?? [];
        $this->whatsapp_enabled = (bool) ($settings['whatsapp_enabled'] ?? false);
        $this->whatsapp_auto_send = (bool) ($settings['whatsapp_auto_send'] ?? false);
        $this->whatsapp_phone = $settings['whatsapp_phone'] ?? '';
    }

    private function resolveTaxRegime(string $rimpeType, bool $isSimplifiedSociety): string
    {
        if ($rimpeType === 'emprendedor') {
            return 'rimpe_emprendedor';
        }

        if ($rimpeType === 'negocio_popular') {
            return 'rimpe_popular';
        }

        if ($isSimplifiedSociety) {
            return 'sociedad_simplificada';
        }

        return 'general';
    }

    private function resolveRimpeType(string $taxRegime): string
    {
        return match ($taxRegime) {
            'rimpe_emprendedor' => 'emprendedor',
            'rimpe_popular' => 'negocio_popular',
            default => 'none',
        };
    }

    public function getReadinessItemsProperty(): array
    {
        $checklist = $this->company?->emissionReadinessChecklist() ?? [
            'basic_data' => false,
            'sri_password' => false,
            'digital_signature' => false,
            'establishments' => false,
        ];

        return [
            [
                'key' => 'basic_data',
                'label' => 'Datos fiscales del emisor',
                'description' => 'RUC, razón social, dirección y contacto',
                'ready' => (bool) ($checklist['basic_data'] ?? false),
            ],
            [
                'key' => 'sri_password',
                'label' => 'Clave del SRI',
                'description' => 'Credenciales para servicios del SRI',
                'ready' => (bool) ($checklist['sri_password'] ?? false),
            ],
            [
                'key' => 'digital_signature',
                'label' => 'Firma electrónica .p12',
                'description' => 'Certificado vigente para firmado XML',
                'ready' => (bool) ($checklist['digital_signature'] ?? false),
            ],
            [
                'key' => 'establishments',
                'label' => 'Establecimiento y punto de emisión',
                'description' => 'Al menos una sucursal activa con punto de emisión',
                'ready' => (bool) ($checklist['establishments'] ?? false),
            ],
        ];
    }

    public function getEmitterReadyProperty(): bool
    {
        return (bool) ($this->company?->isReadyForEmission() ?? false);
    }

    public function getPlanSupportsWhatsAppProperty(): bool
    {
        $tenant = auth()->user()->tenant;
        $features = $tenant?->plan?->features_json ?? [];

        return (bool) ($features['whatsapp'] ?? $features['whatsapp_notifications'] ?? false);
    }

    public function updateWhatsAppSettings(): void
    {
        if (!$this->company) {
            return;
        }

        $this->validate([
            'whatsapp_phone' => $this->whatsapp_enabled ? 'required|string|max:20' : 'nullable|string|max:20',
        ], [
            'whatsapp_phone.required' => 'Ingresa el numero de WhatsApp.',
        ]);

        $settings = $this->company->settings ?? [];
        $settings['whatsapp_enabled'] = $this->whatsapp_enabled;
        $settings['whatsapp_auto_send'] = $this->whatsapp_auto_send;
        $settings['whatsapp_phone'] = $this->whatsapp_enabled ? $this->whatsapp_phone : null;

        if (blank($settings['whatsapp_phone'])) {
            unset($settings['whatsapp_phone']);
        }

        // If WhatsApp is disabled, also disable auto-send
        if (!$this->whatsapp_enabled) {
            $settings['whatsapp_auto_send'] = false;
            $this->whatsapp_auto_send = false;
        }

        $this->company->update(['settings' => $settings]);
        $this->company->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Configuracion de WhatsApp actualizada.',
        ]);
    }

    public function toggleWhatsApp(): void
    {
        if (!$this->company) {
            return;
        }

        $this->whatsapp_enabled = !$this->whatsapp_enabled;

        $settings = $this->company->settings ?? [];
        $settings['whatsapp_enabled'] = $this->whatsapp_enabled;

        // If disabling WhatsApp, also disable auto-send
        if (!$this->whatsapp_enabled) {
            $settings['whatsapp_auto_send'] = false;
            $this->whatsapp_auto_send = false;
        }

        $this->company->update(['settings' => $settings]);
        $this->company->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->whatsapp_enabled
                ? 'WhatsApp habilitado.'
                : 'WhatsApp deshabilitado.',
        ]);
    }

    public function toggleWhatsAppAutoSend(): void
    {
        if (!$this->company) {
            return;
        }

        if (!$this->whatsapp_enabled) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero habilita WhatsApp para activar el envío automático.',
            ]);
            return;
        }

        $this->whatsapp_auto_send = !$this->whatsapp_auto_send;

        $settings = $this->company->settings ?? [];
        $settings['whatsapp_auto_send'] = $this->whatsapp_auto_send;

        $this->company->update(['settings' => $settings]);
        $this->company->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->whatsapp_auto_send
                ? 'Envío automático por WhatsApp activado.'
                : 'Envío automático por WhatsApp desactivado.',
        ]);
    }

    public function updateCompany(): void
    {
        $this->validate([
            'ruc' => [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
                Rule::unique('companies', 'ruc')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->ignore($this->company?->id),
            ],
            'business_name' => 'required|string|max:300',
            'trade_name' => 'nullable|string|max:300',
            'taxpayer_type' => 'required|in:natural,juridical,rise',
            'tax_regime' => 'required|in:general,rimpe_emprendedor,rimpe_popular,sociedad_simplificada',
            'address' => 'required|string|max:300',
            'special_taxpayer' => 'boolean',
            'special_taxpayer_number' => 'nullable|string|max:20|required_if:special_taxpayer,true',
            'retention_agent_number' => 'nullable|string|max:20',
            'artisan_qualified' => 'boolean',
            'artisan_qualification_number' => 'nullable|string|max:50|required_if:artisan_qualified,true',
            'is_transporter' => 'boolean',
            'environment' => 'required|in:1,2',
            'sri_password' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:2048',
        ], [
            'special_taxpayer_number.required_if' => 'Ingresa el numero de resolucion de contribuyente especial.',
            'artisan_qualification_number.required_if' => 'Ingresa el numero de calificacion de artesano.',
        ]);

        $data = [
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name ?: null,
            'taxpayer_type' => $this->taxpayer_type,
            'address' => $this->address,
            'special_taxpayer' => $this->special_taxpayer,
            'special_taxpayer_number' => $this->special_taxpayer ? ($this->special_taxpayer_number ?: null) : null,
            'retention_agent_number' => $this->retention_agent_number ?: null,
            'rimpe_type' => $this->resolveRimpeType($this->tax_regime),
            'obligated_accounting' => $this->accounting_required,
            'sri_environment' => $this->environment,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ];

        if ($this->logo) {
            $path = $this->logo->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        if ($this->company) {
            $this->company->update($data);
        } else {
            $data['tenant_id'] = auth()->user()->tenant_id;
            $this->company = Company::create($data);
        }

        $settings = $this->company->settings ?? [];
        $settings['artisan_qualified'] = $this->artisan_qualified;
        $settings['is_transporter'] = $this->is_transporter;
        $settings['is_simplified_society'] = $this->tax_regime === 'sociedad_simplificada';
        $settings['whatsapp_enabled'] = $this->whatsapp_enabled;
        $settings['whatsapp_auto_send'] = $this->whatsapp_enabled ? $this->whatsapp_auto_send : false;
        $settings['whatsapp_phone'] = $this->whatsapp_enabled ? $this->whatsapp_phone : null;

        if ($this->artisan_qualified && filled($this->artisan_qualification_number)) {
            $settings['artisan_qualification_number'] = $this->artisan_qualification_number;
        } else {
            unset($settings['artisan_qualification_number']);
        }

        if (blank($settings['whatsapp_phone'])) {
            unset($settings['whatsapp_phone']);
        }

        $this->company->settings = $settings;

        if (!empty($this->sri_password)) {
            $this->company->setSriPassword($this->sri_password);
            $this->reset('sri_password');
        }

        $this->company->save();
        $this->company->refresh();
        $this->loadCompanyData();
        $this->loadBranches();
        $this->loadWhatsAppSettings();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Datos de empresa actualizados.',
        ]);
    }

    public function uploadCertificate(): void
    {
        if (!$this->company) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero guarda los datos del emisor.',
            ]);

            return;
        }

        $this->validate([
            'certificate' => 'required|file|mimes:p12,pfx|max:5120',
            'certificate_password' => 'required|string',
        ], [
            'certificate.required' => 'Seleccione el archivo del certificado.',
            'certificate.mimes' => 'El certificado debe ser un archivo .p12 o .pfx.',
            'certificate_password.required' => 'Ingrese la contraseña del certificado.',
        ]);

        try {
            $service = app(SignatureManager::class);
            $service->store($this->company, $this->certificate, $this->certificate_password);

            $this->reset(['certificate', 'certificate_password']);
            $this->company->refresh();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Firma cargada correctamente. Válida hasta: ' . $this->company->signature_expires_at?->format('d/m/Y'),
            ]);

        } catch (\Exception $e) {
            $this->addError('certificate', $e->getMessage());
        }
    }

    public function openBranchModal(?int $branchId = null): void
    {
        $this->resetBranchForm();

        if ($branchId) {
            $branch = $this->company?->branches()->find($branchId);

            if (!$branch) {
                return;
            }

            $this->editingBranchId = $branchId;
            $this->branch_code = $branch->code;
            $this->branch_name = $branch->name;
            $this->branch_address = $branch->address;
            $this->branch_is_main = $branch->is_main;
        }

        $this->showBranchModal = true;
    }

    public function closeBranchModal(): void
    {
        $this->showBranchModal = false;
        $this->resetBranchForm();
    }

    private function resetBranchForm(): void
    {
        $this->editingBranchId = null;
        $this->branch_code = '';
        $this->branch_name = '';
        $this->branch_address = '';
        $this->branch_is_main = false;
    }

    public function saveBranch(): void
    {
        if (!$this->company) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero guarda los datos del emisor.',
            ]);

            return;
        }

        $this->validate([
            'branch_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[0-9]{3}$/',
                Rule::unique('branches', 'code')
                    ->where('company_id', $this->company->id)
                    ->ignore($this->editingBranchId),
            ],
            'branch_name' => 'required|string|max:100',
            'branch_address' => 'required|string|max:300',
        ], [
            'branch_code.required' => 'El código es requerido.',
            'branch_code.size' => 'El código debe tener 3 dígitos.',
            'branch_code.regex' => 'El código solo debe contener números.',
            'branch_code.unique' => 'Ya existe una sucursal con ese código.',
        ]);

        $data = [
            'code' => $this->branch_code,
            'name' => $this->branch_name,
            'address' => $this->branch_address,
            'is_main' => $this->branch_is_main,
            'is_active' => true,
        ];

        if ($this->editingBranchId) {
            $branch = $this->company->branches()->findOrFail($this->editingBranchId);
            $branch->update($data);
        } else {
            $data['tenant_id'] = auth()->user()->tenant_id;
            $data['company_id'] = $this->company->id;
            $branch = Branch::create($data);

            // Crear punto de emisión por defecto
            EmissionPoint::create([
                'tenant_id' => auth()->user()->tenant_id,
                'branch_id' => $branch->id,
                'code' => '001',
                'name' => 'Punto de emisión principal',
                'is_active' => true,
            ]);
        }

        // Si es principal, desmarcar las demás
        if ($this->branch_is_main) {
            Branch::where('company_id', $this->company->id)
                ->where('id', '!=', $branch->id)
                ->update(['is_main' => false]);
        }

        $this->loadBranches();
        $this->closeBranchModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingBranchId ? 'Sucursal actualizada.' : 'Sucursal creada.',
        ]);
    }

    public function deleteBranch(int $branchId): void
    {
        $branch = $this->company?->branches()->find($branchId);

        if (!$branch) {
            return;
        }

        // Verificar si tiene documentos
        if ($branch->documents()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar una sucursal con documentos emitidos.',
            ]);
            return;
        }

        $branch->emissionPoints()->delete();
        $branch->delete();

        $this->loadBranches();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Sucursal eliminada.',
        ]);
    }

    public function openSequentialModal(int $emissionPointId): void
    {
        $emissionPoint = EmissionPoint::find($emissionPointId);

        if (!$emissionPoint) {
            return;
        }

        $this->editingEmissionPointId = $emissionPointId;
        $this->editingEmissionPointName = $emissionPoint->code . ' - ' . $emissionPoint->name;

        $this->sequentialNumbers = [];
        foreach (DocumentType::cases() as $docType) {
            $this->sequentialNumbers[$docType->value] = SequentialNumber::getCurrentNumber($emissionPointId, $docType);
        }

        $this->showSequentialModal = true;
    }

    public function closeSequentialModal(): void
    {
        $this->showSequentialModal = false;
        $this->editingEmissionPointId = null;
        $this->editingEmissionPointName = '';
        $this->sequentialNumbers = [];
    }

    public function saveSequentialNumbers(): void
    {
        $rules = [];
        foreach (DocumentType::cases() as $docType) {
            $rules['sequentialNumbers.' . $docType->value] = 'required|integer|min:0';
        }

        $this->validate($rules, [
            'sequentialNumbers.*.required' => 'El valor es requerido.',
            'sequentialNumbers.*.integer' => 'Debe ser un número entero.',
            'sequentialNumbers.*.min' => 'El valor no puede ser negativo.',
        ]);

        foreach ($this->sequentialNumbers as $docTypeCode => $number) {
            SequentialNumber::resetToNumber(
                $this->editingEmissionPointId,
                DocumentType::from($docTypeCode),
                (int) $number
            );
        }

        $this->closeSequentialModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Secuenciales actualizados correctamente.',
        ]);
    }

    public function getCertificateInfoProperty(): ?array
    {
        if (!$this->company?->signature_path || !$this->company->signature_expires_at) {
            return null;
        }

        $daysRemaining = now()->diffInDays($this->company->signature_expires_at, false);

        return [
            'issued_to' => $this->company->signature_subject,
            'issued_by' => $this->company->signature_issuer,
            'valid_from' => null,
            'valid_until' => $this->company->signature_expires_at,
            'days_remaining' => $daysRemaining,
            'is_expiring_soon' => $daysRemaining <= 30,
        ];
    }

    public function render()
    {
        return view('livewire.panel.settings.company-settings', [
            'certificateInfo' => $this->certificateInfo,
            'readinessItems' => $this->readinessItems,
            'emitterReady' => $this->emitterReady,
        ])->layout('layouts.tenant', ['title' => 'Empresa']);
    }
}
