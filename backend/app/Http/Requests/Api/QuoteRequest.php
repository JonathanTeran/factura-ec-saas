<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de proformas. Los totales NO se aceptan del cliente: el
 * servidor recalcula cada línea y los totales (QuoteService::syncItems).
 */
class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'company_id' => [$required, Rule::exists('companies', 'id')->where('tenant_id', $tenantId)],
            'customer_id' => [$required, Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'quote_number' => [
                'nullable', 'string', 'max:20',
                Rule::unique('quotes', 'quote_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->route('quote')?->id),
            ],
            'issue_date' => [$required, 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'items' => [$required, 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage_code' => ['nullable', 'string', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'customer_id.required' => 'El cliente es requerido.',
            'issue_date.required' => 'La fecha de emisión es requerida.',
            'expiry_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la de emisión.',
            'quote_number.unique' => 'Ya existe una cotización con ese número.',
            'items.required' => 'Debe incluir al menos un ítem.',
            'items.min' => 'Debe incluir al menos un ítem.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.description.required' => 'La descripción es requerida para cada ítem.',
            'items.*.quantity.required' => 'La cantidad es requerida para cada ítem.',
            'items.*.unit_price.required' => 'El precio unitario es requerido para cada ítem.',
        ];
    }
}
