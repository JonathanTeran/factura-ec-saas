<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'amount' => (float) $this->amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'gateway' => $this->gateway,
            'gateway_payment_id' => $this->gateway_payment_id,
            'paid_at' => $this->paid_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'refund_amount' => (float) $this->refund_amount,
            'refund_reason' => $this->refund_reason,
            'billing' => [
                'name' => $this->billing_name,
                'email' => $this->billing_email,
                'identification' => $this->billing_identification,
                'address' => $this->billing_address,
                'phone' => $this->billing_phone,
            ],
            'notes' => $this->notes,
            'is_completed' => $this->isCompleted(),
            'is_refundable' => $this->canRefund(),
            'subscription' => $this->whenLoaded('subscription', fn() => new SubscriptionResource($this->subscription)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
