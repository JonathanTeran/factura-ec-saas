<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmissionPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $emissionPointId = $this->route('emission_point')?->id;
        $branchId = $this->route('branch')?->id ?? $this->input('branch_id');

        return [
            'branch_id' => [
                'required',
                'exists:branches,id',
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[0-9]{3}$/',
                Rule::unique('emission_points')
                    ->where('branch_id', $branchId)
                    ->ignore($emissionPointId),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'El establecimiento es requerido.',
            'branch_id.exists' => 'El establecimiento seleccionado no existe.',
            'code.required' => 'El código de punto de emisión es requerido.',
            'code.size' => 'El código debe tener exactamente 3 dígitos.',
            'code.regex' => 'El código solo debe contener números.',
            'code.unique' => 'Este código ya está registrado para este establecimiento.',
        ];
    }
}
