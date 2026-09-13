<?php

namespace App\Http\Requests\Api;

use App\Models\Tenant\EmissionPoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConvertQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'emission_point_id' => ['required', Rule::exists('emission_points', 'id')->where('tenant_id', $tenantId)],
            'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'send' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string', 'max:5'],
            'payment_term' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*.code' => ['required_with:payment_methods', 'string', 'max:5'],
            'payment_methods.*.amount' => ['required_with:payment_methods', 'numeric', 'min:0'],
            'payment_methods.*.term' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('emission_point_id')) {
                return;
            }

            $quote = $this->route('quote');
            $ok = EmissionPoint::where('id', $this->input('emission_point_id'))
                ->whereHas('branch', fn ($q) => $q->where('company_id', $quote?->company_id))
                ->exists();

            if (! $ok) {
                $validator->errors()->add('emission_point_id', 'El punto de emisión no pertenece a la empresa de la cotización.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'emission_point_id.required' => 'Elige el punto de emisión con el que se facturará.',
            'issue_date.before_or_equal' => 'La fecha de emisión no puede ser futura.',
        ];
    }
}
