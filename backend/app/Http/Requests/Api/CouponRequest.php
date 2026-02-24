<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_num',
                Rule::unique('coupons')->ignore($couponId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'discount_type' => [
                'required',
                Rule::in(['percentage', 'fixed']),
            ],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === 'percentage' && $value > 100) {
                        $fail('El porcentaje de descuento no puede ser mayor a 100.');
                    }
                },
            ],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'applicable_plans' => ['nullable', 'array'],
            'applicable_plans.*' => ['exists:plans,id'],
            'applicable_billing_cycles' => ['nullable', 'array'],
            'applicable_billing_cycles.*' => [Rule::in(['monthly', 'yearly'])],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_tenant' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'first_payment_only' => ['boolean'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:36'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Este código de cupón ya existe.',
            'code.alpha_num' => 'El código solo puede contener letras y números.',
            'name.required' => 'El nombre del cupón es requerido.',
            'discount_type.required' => 'El tipo de descuento es requerido.',
            'discount_type.in' => 'El tipo de descuento debe ser porcentaje o monto fijo.',
            'discount_value.required' => 'El valor del descuento es requerido.',
            'discount_value.min' => 'El valor del descuento no puede ser negativo.',
            'expires_at.after' => 'La fecha de expiración debe ser posterior a la fecha de inicio.',
            'applicable_plans.*.exists' => 'Uno de los planes seleccionados no existe.',
        ];
    }
}
