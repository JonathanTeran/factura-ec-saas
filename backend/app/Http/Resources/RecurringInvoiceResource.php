<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'emission_point_id' => $this->emission_point_id,
            'customer_id' => $this->customer_id,
            'frequency' => $this->frequency,
            'frequency_label' => $this->frequencyLabel(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_issue_date' => $this->next_issue_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'items' => $this->items ?? [],
            'payment_methods' => $this->payment_methods ?? [],
            'additional_info' => $this->additional_info ?? [],
            'notes' => $this->notes,
            'currency' => $this->currency,
            'total_issued' => (int) $this->total_issued,
            'max_issues' => $this->max_issues,
            'last_issued_at' => $this->last_issued_at?->toISOString(),
            'notify_before_issue' => (bool) $this->notify_before_issue,
            'notify_days_before' => (int) $this->notify_days_before,
            'auto_send' => (bool) $this->auto_send,
            'last_error' => $this->last_error,
            'last_error_at' => $this->last_error_at?->toISOString(),
            'estimated_total' => $this->getEstimatedTotal(),
            'can_issue' => $this->canIssue(),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'business_name' => $this->company->business_name,
                'trade_name' => $this->company->trade_name,
                'ruc' => $this->company->ruc,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'emission_point' => $this->whenLoaded('emissionPoint', fn () => [
                'id' => $this->emissionPoint->id,
                'code' => $this->emissionPoint->code,
                'description' => $this->emissionPoint->description,
            ]),
            'generated_documents_count' => $this->whenCounted('generatedDocuments'),
            'recent_documents' => $this->whenLoaded('generatedDocuments', fn () => DocumentResource::collection($this->generatedDocuments)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
