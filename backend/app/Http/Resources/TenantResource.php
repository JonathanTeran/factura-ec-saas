<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'is_on_trial' => $this->isOnTrial(),
            'documents_issued_this_month' => $this->documents_issued_this_month,
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'subscription' => $this->whenLoaded('currentSubscription', fn () => [
                'id' => $this->currentSubscription->id,
                'status' => $this->currentSubscription->status,
                'billing_cycle' => $this->currentSubscription->billing_cycle,
                'ends_at' => $this->currentSubscription->ends_at?->toISOString(),
            ]),
            'limits' => [
                'max_documents_per_month' => $this->plan?->max_documents_per_month ?? 10,
                'max_users' => $this->plan?->max_users ?? 1,
                'max_companies' => $this->plan?->max_companies ?? 1,
                'max_emission_points' => $this->plan?->max_emission_points ?? 1,
            ],
            'features' => [
                'has_electronic_signature' => (bool) $this->plan?->has_electronic_signature,
                'has_api_access' => (bool) $this->plan?->has_api_access,
                'has_inventory' => (bool) $this->plan?->has_inventory,
                'has_pos' => (bool) $this->plan?->has_pos,
                'has_recurring_invoices' => (bool) $this->plan?->has_recurring_invoices,
                'has_proformas' => (bool) $this->plan?->has_proformas,
                'has_ats' => (bool) $this->plan?->has_ats,
                'has_thermal_printer' => (bool) $this->plan?->has_thermal_printer,
                'has_advanced_reports' => (bool) $this->plan?->has_advanced_reports,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
