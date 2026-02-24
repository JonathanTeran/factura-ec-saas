<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($productId),
            ],
            'sku' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['product', 'service'])],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'tax_code' => ['nullable', 'string', 'max:5'],
            'tax_percentage_code' => ['nullable', 'string', 'max:5'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'track_inventory' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es requerido.',
            'code.unique' => 'Este código ya está registrado.',
            'name.required' => 'El nombre es requerido.',
            'type.required' => 'El tipo es requerido.',
            'unit_price.required' => 'El precio unitario es requerido.',
            'unit_price.numeric' => 'El precio unitario debe ser un número.',
            'unit_price.min' => 'El precio unitario no puede ser negativo.',
        ];
    }
}
