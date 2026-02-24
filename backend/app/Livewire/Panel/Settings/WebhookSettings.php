<?php

namespace App\Livewire\Panel\Settings;

use App\Models\Tenant\WebhookEndpoint;
use Livewire\Component;

class WebhookSettings extends Component
{
    public bool $showCreateForm = false;
    public bool $showSecret = false;

    // Form fields
    public string $url = '';
    public array $selectedEvents = [];

    // Edit mode
    public ?int $editingId = null;

    // Secret display
    public ?string $displayedSecret = null;

    protected function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:500'],
            'selectedEvents' => ['required', 'array', 'min:1'],
            'selectedEvents.*' => ['in:' . implode(',', WebhookEndpoint::$availableEvents)],
        ];
    }

    protected function messages(): array
    {
        return [
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'Ingresa una URL válida (ej: https://tu-app.com/webhook).',
            'selectedEvents.required' => 'Selecciona al menos un evento.',
            'selectedEvents.min' => 'Selecciona al menos un evento.',
        ];
    }

    public function getEndpointsProperty()
    {
        return WebhookEndpoint::where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAvailableEventsProperty(): array
    {
        return [
            'document.authorized' => 'Documento Autorizado',
            'document.rejected' => 'Documento Rechazado',
            'document.created' => 'Documento Creado',
            'document.signed' => 'Documento Firmado',
            'document.voided' => 'Documento Anulado',
            'document.failed' => 'Documento Fallido',
        ];
    }

    public function getHasWebhooksFeatureProperty(): bool
    {
        return auth()->user()->tenant?->currentPlan?->has_webhooks ?? false;
    }

    public function create(): void
    {
        if (!$this->hasWebhooksFeature) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Tu plan actual no incluye webhooks. Actualiza tu plan para usar esta funcionalidad.',
            ]);
            return;
        }

        $this->validate();

        $secret = WebhookEndpoint::generateSecret();

        WebhookEndpoint::create([
            'tenant_id' => auth()->user()->tenant_id,
            'url' => $this->url,
            'secret' => $secret,
            'events' => $this->selectedEvents,
            'is_active' => true,
        ]);

        $this->displayedSecret = $secret;
        $this->resetForm();
        $this->showCreateForm = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Webhook creado exitosamente. Guarda el secret, no se mostrará de nuevo.',
        ]);
    }

    public function edit(int $id): void
    {
        $endpoint = $this->findEndpoint($id);
        if (!$endpoint) {
            return;
        }

        $this->editingId = $id;
        $this->url = $endpoint->url;
        $this->selectedEvents = $endpoint->events;
        $this->showCreateForm = true;
    }

    public function update(): void
    {
        $this->validate();

        $endpoint = $this->findEndpoint($this->editingId);
        if (!$endpoint) {
            return;
        }

        $endpoint->update([
            'url' => $this->url,
            'events' => $this->selectedEvents,
        ]);

        $this->resetForm();
        $this->editingId = null;
        $this->showCreateForm = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Webhook actualizado exitosamente.',
        ]);
    }

    public function toggleActive(int $id): void
    {
        $endpoint = $this->findEndpoint($id);
        if (!$endpoint) {
            return;
        }

        $endpoint->update(['is_active' => !$endpoint->is_active]);

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $endpoint->is_active ? 'Webhook activado.' : 'Webhook desactivado.',
        ]);
    }

    public function regenerateSecret(int $id): void
    {
        $endpoint = $this->findEndpoint($id);
        if (!$endpoint) {
            return;
        }

        $secret = WebhookEndpoint::generateSecret();
        $endpoint->update(['secret' => $secret]);

        $this->displayedSecret = $secret;

        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Secret regenerado. Actualiza tu aplicación con el nuevo secret.',
        ]);
    }

    public function resetFailures(int $id): void
    {
        $endpoint = $this->findEndpoint($id);
        if (!$endpoint) {
            return;
        }

        $endpoint->update(['failure_count' => 0, 'is_active' => true]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Contador de errores reiniciado y webhook reactivado.',
        ]);
    }

    public function delete(int $id): void
    {
        $endpoint = $this->findEndpoint($id);
        if (!$endpoint) {
            return;
        }

        $endpoint->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Webhook eliminado.',
        ]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showCreateForm = false;
    }

    public function dismissSecret(): void
    {
        $this->displayedSecret = null;
    }

    protected function resetForm(): void
    {
        $this->url = '';
        $this->selectedEvents = [];
    }

    protected function findEndpoint(?int $id): ?WebhookEndpoint
    {
        if (!$id) {
            return null;
        }

        return WebhookEndpoint::where('tenant_id', auth()->user()->tenant_id)
            ->find($id);
    }

    public function render()
    {
        return view('livewire.panel.settings.webhook-settings', [
            'endpoints' => $this->endpoints,
            'availableEvents' => $this->availableEvents,
            'hasFeature' => $this->hasWebhooksFeature,
        ])->layout('layouts.tenant', ['title' => 'Webhooks']);
    }
}
