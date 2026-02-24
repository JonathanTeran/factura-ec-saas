<?php

namespace App\Http\Requests\Api;

use App\Enums\IdentificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'identification_type' => ['required', Rule::enum(IdentificationType::class)],
            'identification_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($customerId),
            ],
            'name' => ['required', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'identification_type.required' => 'El tipo de identificación es requerido.',
            'identification_number.required' => 'El número de identificación es requerido.',
            'identification_number.unique' => 'Este número de identificación ya está registrado.',
            'name.required' => 'El nombre es requerido.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }
}
