<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'is_main' => (bool) $this->is_main,
            'is_active' => (bool) $this->is_active,
            'emission_points' => $this->whenLoaded('emissionPoints', fn () => EmissionPointResource::collection($this->emissionPoints)),
            'emission_points_count' => $this->whenCounted('emissionPoints'),
        ];
    }
}
