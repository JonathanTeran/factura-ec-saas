<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value && $categoryId && $value == $categoryId) {
                        $fail('Una categoría no puede ser su propio padre.');
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('parent_id', $this->input('parent_id'))
                    ->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es requerido.',
            'name.unique' => 'Ya existe una categoría con este nombre en el mismo nivel.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ];
    }
}
