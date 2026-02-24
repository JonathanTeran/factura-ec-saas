<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan' => $this->whenLoaded('plan', fn() => new PlanResource($this->plan)),
            'plan_id' => $this->plan_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'billing_cycle' => $this->billing_cycle,
            'billing_cycle_label' => $this->getBillingCycleLabel(),
            'price' => (float) $this->price,
            'discount_amount' => (float) $this->discount_amount,
            'final_price' => (float) $this->final_price,
            'currency' => $this->currency,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'canceled_at' => $this->canceled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'auto_renew' => $this->auto_renew,
            'payment_method' => $this->payment_method,
            'last_payment_at' => $this->last_payment_at?->toISOString(),
            'next_payment_at' => $this->next_payment_at?->toISOString(),
            'days_until_expiration' => $this->daysUntilExpiration(),
            'on_trial' => $this->onTrial(),
            'is_active' => $this->isActive(),
            'is_canceled' => $this->isCanceled(),
            'is_past_due' => $this->isPastDue(),
            'coupon' => $this->whenLoaded('coupon', fn() => new CouponResource($this->coupon)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
