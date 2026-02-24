<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'main_code' => $this->main_code,
            'aux_code' => $this->aux_code,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'subtotal' => (float) $this->subtotal,
            'tax_code' => $this->tax_code,
            'tax_percentage_code' => $this->tax_percentage_code,
            'tax_rate' => (float) $this->tax_rate,
            'tax_base' => (float) $this->tax_base,
            'tax_value' => (float) $this->tax_value,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
        ];
    }
}
