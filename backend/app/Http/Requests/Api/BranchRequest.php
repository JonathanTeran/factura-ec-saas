<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;
        $companyId = $this->route('company')?->id ?? $this->input('company_id');

        return [
            'company_id' => [
                'required',
                'exists:companies,id',
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[0-9]{3}$/',
                Rule::unique('branches')
                    ->where('company_id', $companyId)
                    ->ignore($branchId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'code.required' => 'El código de establecimiento es requerido.',
            'code.size' => 'El código debe tener exactamente 3 dígitos.',
            'code.regex' => 'El código solo debe contener números.',
            'code.unique' => 'Este código ya está registrado para esta empresa.',
            'name.required' => 'El nombre del establecimiento es requerido.',
            'address.required' => 'La dirección es requerida.',
        ];
    }
}
