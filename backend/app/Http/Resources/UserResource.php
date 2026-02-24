<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url ? asset('storage/' . $this->avatar_url) : null,
            'role' => $this->role,
            'role_label' => $this->role?->label(),
            'is_active' => $this->is_active,
            'current_company_id' => $this->current_company_id,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'tenant' => $this->whenLoaded('tenant', fn () => new TenantResource($this->tenant)),
        ];
    }
}
