<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'exists:plans,id',
            ],
            'billing_cycle' => [
                'required',
                Rule::in(['monthly', 'yearly']),
            ],
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'payment_method' => [
                'required',
                Rule::in(['credit_card', 'debit_card', 'bank_transfer', 'paypal']),
            ],
            'auto_renew' => ['boolean'],

            // Datos de facturación
            'billing_name' => ['required', 'string', 'max:300'],
            'billing_identification' => ['required', 'string', 'max:20'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:300'],
            'billing_phone' => ['nullable', 'string', 'max:20'],

            // Datos de tarjeta (si aplica)
            'card_token' => ['required_if:payment_method,credit_card,debit_card', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'El plan es requerido.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
            'billing_cycle.required' => 'El ciclo de facturación es requerido.',
            'billing_cycle.in' => 'El ciclo debe ser mensual o anual.',
            'payment_method.required' => 'El método de pago es requerido.',
            'billing_name.required' => 'El nombre de facturación es requerido.',
            'billing_identification.required' => 'La identificación de facturación es requerida.',
            'billing_email.required' => 'El correo de facturación es requerido.',
            'card_token.required_if' => 'El token de tarjeta es requerido para pagos con tarjeta.',
        ];
    }
}
