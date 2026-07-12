<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->main_code,
            'sku' => $this->aux_code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type === 'service' ? 'Servicio' : 'Bien',
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit_price' => (float) $this->unit_price,
            'cost' => (float) $this->cost_price,
            'tax_code' => $this->tax_code,
            'tax_percentage_code' => $this->tax_percentage_code,
            'tax_rate' => (float) $this->tax_rate,
            'track_inventory' => (bool) $this->track_inventory,
            'stock' => $this->track_inventory ? (int) $this->current_stock : null,
            'min_stock' => $this->track_inventory ? (int) $this->min_stock : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
