<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = is_object($this->status) ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'status' => $status,
            'status_label' => is_object($this->status) && method_exists($this->status, 'label') ? $this->status->label() : null,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_expired' => $this->isExpired(),
            'subtotal' => (float) $this->subtotal,
            'total_discount' => (float) $this->total_discount,
            'total_tax' => (float) $this->total_tax,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'payment_terms' => $this->payment_terms,
            'converted_to_document_id' => $this->converted_to_document_id,
            'sent_at' => $this->sent_at?->toISOString(),
            'sent_to' => $this->sent_to,
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'converted_at' => $this->converted_at?->toISOString(),
            'can_edit' => $this->canBeEdited(),
            'can_send' => $this->canBeSent(),
            'can_accept' => $this->canBeAccepted(),
            'can_reject' => $this->canBeRejected(),
            'can_convert' => $this->canBeConverted(),
            'can_delete' => $this->canBeDeleted(),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'business_name' => $this->company->business_name,
                'trade_name' => $this->company->trade_name,
                'ruc' => $this->company->ruc,
            ]),
            'converted_document' => $this->whenLoaded('convertedDocument', fn () => $this->convertedDocument ? [
                'id' => $this->convertedDocument->id,
                'document_number' => $this->convertedDocument->document_number,
                'status' => $this->convertedDocument->status?->value,
                'status_label' => $this->convertedDocument->status?->label(),
                'issue_date' => $this->convertedDocument->issue_date?->toDateString(),
                'total' => (float) $this->convertedDocument->total,
            ] : null),
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
