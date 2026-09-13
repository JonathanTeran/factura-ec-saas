<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Nunca expone el hash; solo el prefijo para identificar la llave en la UI. */
class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key_prefix' => $this->key_prefix,
            'scopes' => $this->scopes(),
            'rate_limit_per_minute' => (int) $this->rate_limit_per_minute,
            'effective_rate_limit' => $this->effectiveRateLimit(),
            'last_used_at' => $this->last_used_at?->toISOString(),
            'last_used_ip' => $this->last_used_ip,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'is_expired' => $this->isExpired(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
