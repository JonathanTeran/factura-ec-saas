<?php

namespace App\Livewire\Settings;

use App\Models\Billing\Plan;
use Livewire\Component;

class Subscription extends Component
{
    public function render()
    {
        $tenant = auth()->user()->tenant->load(['plan', 'currentSubscription']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.settings.subscription', compact('tenant', 'plans'))
            ->layout('layouts.tenant', ['title' => 'Suscripción']);
    }
}
