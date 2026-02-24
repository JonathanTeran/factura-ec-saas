<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'discount_label' => $this->getDiscountLabel(),
            'max_discount_amount' => (float) $this->max_discount_amount,
            'min_purchase_amount' => (float) $this->min_purchase_amount,
            'applicable_plans' => $this->applicable_plans,
            'applicable_billing_cycles' => $this->applicable_billing_cycles,
            'max_uses' => $this->max_uses,
            'max_uses_per_tenant' => $this->max_uses_per_tenant,
            'current_uses' => $this->current_uses,
            'remaining_uses' => $this->max_uses ? $this->max_uses - $this->current_uses : null,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'first_payment_only' => $this->first_payment_only,
            'duration_months' => $this->duration_months,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'has_reached_max_uses' => $this->hasReachedMaxUses(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
