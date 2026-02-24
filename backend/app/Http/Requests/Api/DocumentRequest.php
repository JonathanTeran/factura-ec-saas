<?php

namespace App\Http\Requests\Api;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'emission_point_id' => ['required', 'exists:emission_points,id'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'issue_date' => ['nullable', 'date'],
            'subtotal_no_tax' => ['nullable', 'numeric', 'min:0'],
            'subtotal_0' => ['nullable', 'numeric', 'min:0'],
            'subtotal_5' => ['nullable', 'numeric', 'min:0'],
            'subtotal_12' => ['nullable', 'numeric', 'min:0'],
            'subtotal_15' => ['nullable', 'numeric', 'min:0'],
            'total_tax' => ['nullable', 'numeric', 'min:0'],
            'total_discount' => ['nullable', 'numeric', 'min:0'],
            // Compatibilidad con clientes existentes
            'tax_12' => ['nullable', 'numeric', 'min:0'],
            'tax_15' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tip' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*.code' => ['required_with:payment_methods', 'string', 'max:5'],
            'payment_methods.*.amount' => ['required_with:payment_methods', 'numeric', 'min:0'],
            // Compatibilidad con clientes existentes
            'payment_method' => ['nullable', 'string', 'max:5'],
            'payment_term' => ['nullable', 'integer', 'min:0'],
            'additional_info' => ['nullable', 'array'],
            'additional_info.*' => ['string', 'max:300'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.main_code' => ['required', 'string', 'max:50'],
            'items.*.aux_code' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.tax_code' => ['nullable', 'string', 'max:5'],
            'items.*.tax_percentage_code' => ['nullable', 'string', 'max:5'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_base' => ['required', 'numeric', 'min:0'],
            'items.*.tax_value' => ['required', 'numeric', 'min:0'],
        ];

        // For credit notes and debit notes, require reference document
        if ($this->input('document_type') === DocumentType::NOTA_CREDITO->value ||
            $this->input('document_type') === DocumentType::NOTA_DEBITO->value) {
            $rules['reference_document_id'] = ['required', 'exists:electronic_documents,id'];
            $rules['modification_reason'] = ['required', 'string', 'max:300'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'customer_id.required' => 'El cliente es requerido.',
            'emission_point_id.required' => 'El punto de emisión es requerido.',
            'document_type.required' => 'El tipo de documento es requerido.',
            'total.required' => 'El total es requerido.',
            'items.required' => 'Debe incluir al menos un item.',
            'items.min' => 'Debe incluir al menos un item.',
            'items.*.main_code.required' => 'El código principal es requerido para cada item.',
            'items.*.description.required' => 'La descripción es requerida para cada item.',
            'items.*.quantity.required' => 'La cantidad es requerida para cada item.',
            'items.*.unit_price.required' => 'El precio unitario es requerido para cada item.',
            'reference_document_id.required' => 'El documento de referencia es requerido para notas de crédito/débito.',
            'modification_reason.required' => 'El motivo de modificación es requerido.',
        ];
    }
}
