<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_url' => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
            'sri_environment' => $this->sri_environment,
            'sri_environment_label' => $this->sri_environment === '2' ? 'Producción' : 'Pruebas',
            'is_special_taxpayer' => (bool) $this->special_taxpayer,
            'special_taxpayer_number' => $this->special_taxpayer_number,
            'retention_agent_number' => $this->retention_agent_number,
            'taxpayer_type' => $this->taxpayer_type,
            'rimpe_type' => $this->rimpe_type,
            'is_accounting_required' => (bool) $this->obligated_accounting,
            'has_valid_signature' => $this->hasValidSignature(),
            'signature_expires_at' => $this->signature_expires_at?->toISOString(),
            'has_sri_password' => $this->hasSriPassword(),
            'is_ready_for_emission' => $this->isReadyForEmission(),
            'is_active' => (bool) $this->is_active,
            'branches' => $this->whenLoaded('branches', fn () => BranchResource::collection($this->branches)),
            'branches_count' => $this->whenCounted('branches'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
