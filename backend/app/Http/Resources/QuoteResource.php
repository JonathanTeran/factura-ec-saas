<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
            'status_label' => is_object($this->status) && method_exists($this->status, 'label') ? $this->status->label() : null,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'total_discount' => (float) $this->total_discount,
            'total_tax' => (float) $this->total_tax,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'payment_terms' => $this->payment_terms,
            'converted_to_document_id' => $this->converted_to_document_id,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($it) => [
                'id' => $it->id,
                'product_id' => $it->product_id,
                'description' => $it->description,
                'quantity' => (float) $it->quantity,
                'unit_price' => (float) $it->unit_price,
                'discount' => (float) $it->discount,
                'tax_rate' => (float) $it->tax_rate,
                'subtotal' => (float) $it->subtotal,
                'tax_value' => (float) $it->tax_value,
                'total' => (float) $it->total,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
