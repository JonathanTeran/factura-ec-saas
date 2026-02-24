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
            'code' => $this->code,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type === 'service' ? 'Servicio' : 'Bien',
            'unit_price' => (float) $this->unit_price,
            'cost' => (float) $this->cost,
            'tax_code' => $this->tax_code,
            'tax_percentage_code' => $this->tax_percentage_code,
            'tax_rate' => (float) $this->tax_rate,
            'track_inventory' => (bool) $this->track_inventory,
            'stock' => $this->track_inventory ? (int) $this->stock : null,
            'min_stock' => $this->track_inventory ? (int) $this->min_stock : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
