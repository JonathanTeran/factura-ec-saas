<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_type_label' => $this->document_type?->label(),
            'document_number' => $this->document_number,
            'access_key' => $this->access_key,
            'environment' => $this->environment,
            'environment_label' => $this->environment === '2' ? 'Producción' : 'Pruebas',
            'establishment_code' => $this->branch?->code,
            'emission_point_code' => $this->emissionPoint?->code,
            'series' => $this->series,
            'sequential' => $this->sequential,
            'issue_date' => $this->issue_date?->toDateString(),
            'issue_datetime' => $this->issue_date?->toISOString(),
            'currency' => $this->currency,
            'subtotal_no_tax' => (float) $this->subtotal_no_tax,
            'subtotal_0' => (float) $this->subtotal_0,
            'subtotal_5' => (float) $this->subtotal_5,
            'subtotal_12' => (float) $this->subtotal_12,
            'subtotal_15' => (float) $this->subtotal_15,
            'total_discount' => (float) $this->total_discount,
            'total_tax' => (float) $this->total_tax,
            'tip' => (float) $this->tip,
            'total' => (float) $this->total,
            'payment_methods' => $this->payment_methods,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'authorization_number' => $this->authorization_number,
            'authorization_date' => $this->authorization_date?->toISOString(),
            'sri_messages' => $this->sri_response['messages'] ?? null,
            'additional_info' => $this->additional_info,
            'has_ride' => !empty($this->ride_pdf_path ?? $this->ride_path),
            'has_xml' => !empty($this->xml_signed_path),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'items' => $this->whenLoaded('items', fn () => DocumentItemResource::collection($this->items)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
