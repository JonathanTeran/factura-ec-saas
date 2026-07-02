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
            'company_id' => ['required', Rule::exists('companies', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'emission_point_id' => ['required', Rule::exists('emission_points', 'id')->where('tenant_id', $this->user()->tenant_id)],
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
            // Valores string (campos SRI nombre/valor) o arrays anidados
            // (ej. `destinatarios` de guía de remisión — ver DocumentBuilder::waybill).
            'additional_info' => ['nullable', 'array'],
            'additional_info.*' => [
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && mb_strlen($value) > 300) {
                        $fail("El campo {$attribute} no debe superar 300 caracteres.");
                    }
                    if (! is_string($value) && ! is_array($value)) {
                        $fail("El campo {$attribute} debe ser texto o una lista.");
                    }
                },
            ],
            'items' => ['required_unless:document_type,07', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('tenant_id', $this->user()->tenant_id)],
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
            $rules['reference_document_id'] = [
                'required',
                Rule::exists('electronic_documents', 'id')
                    ->where('tenant_id', $this->user()->tenant_id),
            ];
            $rules['modification_reason'] = ['required', 'string', 'max:300'];
        }

        // For withholding receipts (comprobante de retención), require withholding details
        if ($this->input('document_type') === DocumentType::RETENCION->value) {
            $rules['withholding_details'] = ['required', 'array', 'min:1'];
            $rules['withholding_details.*.support_doc_code'] = ['required', 'string', 'max:5'];
            $rules['withholding_details.*.support_doc_number'] = ['required', 'string', 'max:20'];
            $rules['withholding_details.*.support_doc_date'] = ['required', 'date'];
            $rules['withholding_details.*.support_doc_total'] = ['nullable', 'numeric', 'min:0'];
            $rules['withholding_details.*.support_reason_code'] = ['nullable', 'string', 'max:5'];
            $rules['withholding_details.*.tax_type'] = ['required', Rule::in(['renta', 'iva'])];
            $rules['withholding_details.*.retention_code'] = ['required', 'string', 'max:10'];
            $rules['withholding_details.*.tax_base'] = ['required', 'numeric', 'min:0'];
            $rules['withholding_details.*.retention_rate'] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules['withholding_details.*.retained_value'] = ['required', 'numeric', 'min:0'];
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
            'withholding_details.required' => 'Debe incluir al menos una retención.',
            'withholding_details.min' => 'Debe incluir al menos una retención.',
            'withholding_details.*.support_doc_code.required' => 'El tipo de documento sustento es requerido.',
            'withholding_details.*.support_doc_number.required' => 'El número de documento sustento es requerido.',
            'withholding_details.*.support_doc_date.required' => 'La fecha del documento sustento es requerida.',
            'withholding_details.*.tax_type.required' => 'El tipo de impuesto es requerido para cada retención.',
            'withholding_details.*.retention_code.required' => 'El código de retención es requerido para cada retención.',
            'withholding_details.*.tax_base.required' => 'La base imponible es requerida para cada retención.',
            'withholding_details.*.retention_rate.required' => 'El porcentaje de retención es requerido.',
            'withholding_details.*.retained_value.required' => 'El valor retenido es requerido.',
        ];
    }
}
