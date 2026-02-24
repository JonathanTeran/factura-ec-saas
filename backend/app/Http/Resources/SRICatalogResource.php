<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SRICatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'catalog_type' => $this->catalog_type->value,
            'catalog_type_label' => $this->catalog_type->label(),
            'code' => $this->code,
            'description' => $this->description,
            'percentage' => $this->percentage ? (float) $this->percentage : null,
            'is_active' => $this->is_active,
            'parent_code' => $this->parent_code,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
        ];
    }
}
