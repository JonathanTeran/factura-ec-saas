<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_monthly' => (float) $this->price_monthly,
            'price_yearly' => (float) $this->price_yearly,
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
            'limits' => [
                'max_documents_per_month' => $this->max_documents_per_month,
                'max_users' => $this->max_users,
                'max_companies' => $this->max_companies,
                'max_emission_points' => $this->max_emission_points,
            ],
            'features' => [
                'has_electronic_signature' => (bool) $this->has_electronic_signature,
                'has_api_access' => (bool) $this->has_api_access,
                'has_inventory' => (bool) $this->has_inventory,
                'has_pos' => (bool) $this->has_pos,
                'has_recurring_invoices' => (bool) $this->has_recurring_invoices,
                'has_proformas' => (bool) $this->has_proformas,
                'has_ats' => (bool) $this->has_ats,
                'has_thermal_printer' => (bool) $this->has_thermal_printer,
                'has_advanced_reports' => (bool) $this->has_advanced_reports,
                'has_whitelabel_ride' => (bool) $this->has_whitelabel_ride,
                'has_webhooks' => (bool) $this->has_webhooks,
                'has_client_portal' => (bool) $this->has_client_portal,
            ],
            'support' => [
                'level' => $this->support_level,
                'response_hours' => $this->support_response_hours,
            ],
            'is_featured' => (bool) $this->is_featured,
        ];
    }
}
