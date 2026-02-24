<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'ruc' => [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
                Rule::unique('companies')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($companyId),
            ],
            'business_name' => ['required', 'string', 'max:300'],
            'trade_name' => ['nullable', 'string', 'max:300'],
            'taxpayer_type' => ['required', Rule::in(['natural', 'juridical', 'rise'])],
            'rimpe_type' => ['nullable', Rule::in(['none', 'emprendedor', 'negocio_popular'])],
            'address' => ['required', 'string', 'max:300'],
            'special_taxpayer' => ['boolean'],
            'special_taxpayer_number' => ['nullable', 'string', 'max:20'],
            'retention_agent_number' => ['nullable', 'string', 'max:20'],
            'obligated_accounting' => ['boolean'],
            'sri_environment' => ['required', Rule::in(['1', '2'])],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'sri_password' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.required' => 'El RUC es requerido.',
            'ruc.size' => 'El RUC debe tener exactamente 13 dígitos.',
            'ruc.regex' => 'El RUC solo debe contener números.',
            'ruc.unique' => 'Este RUC ya está registrado.',
            'business_name.required' => 'La razón social es requerida.',
            'address.required' => 'La dirección matriz es requerida.',
            'sri_environment.required' => 'El ambiente es requerido.',
            'sri_environment.in' => 'El ambiente debe ser 1 (Pruebas) o 2 (Producción).',
        ];
    }
}
