<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'product_id' => $this->product_id,
            'movement_type' => $this->movement_type->value,
            'movement_type_label' => $this->movement_type->label(),
            'movement_type_icon' => $this->movement_type->icon(),
            'movement_type_color' => $this->getMovementColor(),
            'quantity' => (float) $this->quantity,
            'absolute_quantity' => $this->getAbsoluteQuantity(),
            'stock_before' => (float) $this->stock_before,
            'stock_after' => (float) $this->stock_after,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'notes' => $this->notes,
            'is_incoming' => $this->isIncoming(),
            'is_outgoing' => $this->isOutgoing(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
