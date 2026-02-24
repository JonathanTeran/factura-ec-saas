<?php

namespace App\Livewire\Portal;

use App\Services\Portal\CustomerPortalService;
use Livewire\Component;

class PortalDashboard extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $session = request()->attributes->get('portal_session');
        $service = app(CustomerPortalService::class);
        $this->stats = $service->getDashboardStats($session);
    }

    public function render()
    {
        return view('livewire.portal.portal-dashboard')
            ->layout('layouts.portal', ['title' => 'Dashboard']);
    }
}
