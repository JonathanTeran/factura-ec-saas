<?php

namespace App\Livewire\Panel\Settings;

use App\Models\Tenant\ApiKey;
use Livewire\Component;

class ApiKeySettings extends Component
{
    public bool $showCreateForm = false;
    public string $name = '';
    public array $selectedPermissions = [];
    public int $rateLimit = 60;
    public string $expiresAt = '';
    public ?string $newlyCreatedKey = null;

    protected function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'rateLimit' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la API key es obligatorio.',
            'name.max'      => 'El nombre no puede superar 100 caracteres.',
        ];
    }

    public function getApiKeysProperty()
    {
        return ApiKey::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->get();
    }

    public function getAvailablePermissionsProperty(): array
    {
        return ApiKey::availablePermissions();
    }

    public function getHasApiAccessProperty(): bool
    {
        return auth()->user()->tenant?->currentPlan?->has_api_access ?? false;
    }

    public function create(): void
    {
        if (!$this->hasApiAccess) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Tu plan no incluye acceso API. Actualiza tu plan para usar esta funcionalidad.']);
            return;
        }

        $this->validate();

        $plainKey = ApiKey::generatePlainKey();

        ApiKey::create([
            'tenant_id'            => auth()->user()->tenant_id,
            'name'                 => $this->name,
            'key_hash'             => ApiKey::hashKey($plainKey),
            'key_prefix'           => ApiKey::prefixFrom($plainKey),
            'permissions'          => empty($this->selectedPermissions) ? null : $this->selectedPermissions,
            'rate_limit_per_minute' => $this->rateLimit,
            'expires_at'           => $this->expiresAt ?: null,
            'is_active'            => true,
        ]);

        $this->newlyCreatedKey = $plainKey;
        $this->resetForm();
        $this->showCreateForm = false;

        $this->dispatch('notify', ['type' => 'success', 'message' => 'API Key creada. Guarda la clave, no se mostrará de nuevo.']);
    }

    public function revoke(int $id): void
    {
        $key = ApiKey::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $key->update(['is_active' => false]);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'API Key revocada.']);
    }

    public function delete(int $id): void
    {
        ApiKey::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'API Key eliminada.']);
    }

    public function dismissKey(): void
    {
        $this->newlyCreatedKey = null;
    }

    public function resetForm(): void
    {
        $this->name               = '';
        $this->selectedPermissions = [];
        $this->rateLimit          = 60;
        $this->expiresAt          = '';
    }

    public function render()
    {
        return view('livewire.panel.settings.api-key-settings', [
            'apiKeys'              => $this->apiKeys,
            'availablePermissions' => $this->availablePermissions,
            'hasApiAccess'         => $this->hasApiAccess,
        ])->layout('layouts.tenant', ['title' => 'API Keys']);
    }
}
