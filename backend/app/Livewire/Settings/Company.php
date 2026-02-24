<?php

namespace App\Livewire\Settings;

use App\Models\Tenant\Company as CompanyModel;
use Livewire\Component;

class Company extends Component
{
    public function render()
    {
        $companies = CompanyModel::where('tenant_id', auth()->user()->tenant_id)
            ->with('branches.emissionPoints')
            ->orderBy('business_name')
            ->get();

        return view('livewire.settings.company', compact('companies'))
            ->layout('layouts.tenant', ['title' => 'Empresas']);
    }
}
