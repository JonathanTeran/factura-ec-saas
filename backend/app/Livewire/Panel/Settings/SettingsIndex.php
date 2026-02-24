<?php

namespace App\Livewire\Panel\Settings;

use Livewire\Component;

class SettingsIndex extends Component
{
    public function getSettingsSectionsProperty(): array
    {
        return [
            [
                'title' => 'Perfil',
                'description' => 'Actualiza tu información personal y contraseña.',
                'icon' => 'heroicon-o-user-circle',
                'route' => 'panel.settings.profile',
                'color' => 'blue',
            ],
            [
                'title' => 'Empresa',
                'description' => 'Configura los datos de tu empresa y certificado digital.',
                'icon' => 'heroicon-o-building-office',
                'route' => 'panel.settings.company',
                'color' => 'indigo',
            ],
            [
                'title' => 'Facturación',
                'description' => 'Gestiona tu suscripción y métodos de pago.',
                'icon' => 'heroicon-o-credit-card',
                'route' => 'panel.settings.billing',
                'color' => 'green',
            ],
            [
                'title' => 'Webhooks',
                'description' => 'Configura endpoints para recibir notificaciones de eventos.',
                'icon' => 'heroicon-o-link',
                'route' => 'panel.settings.webhooks',
                'color' => 'purple',
            ],
        ];
    }

    public function getQuickStatsProperty(): array
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        $subscription = $tenant?->activeSubscription;
        $plan = $tenant?->currentPlan;

        return [
            'plan_name' => $plan?->name ?? 'Sin plan',
            'subscription_status' => $subscription?->status->value ?? 'inactive',
            'documents_used' => $tenant?->documentsThisMonth()->count() ?? 0,
            'documents_limit' => $plan?->max_documents_per_month ?? 0,
            'users_count' => $tenant?->users()->count() ?? 0,
            'users_limit' => $plan?->max_users ?? 0,
            'certificate_expires' => $tenant?->companies()->first()?->signature_expires_at,
        ];
    }

    public function render()
    {
        return view('livewire.panel.settings.settings-index', [
            'sections' => $this->settingsSections,
            'quickStats' => $this->quickStats,
        ])->layout('layouts.tenant', ['title' => 'Configuración']);
    }
}
