<?php

namespace App\Http\Requests\Api;

use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\RecurringInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de facturas recurrentes. En edición (PUT/PATCH) todos los
 * campos son opcionales y la coherencia empresa → establecimiento → punto
 * de emisión se valida contra los valores que queden vigentes.
 */
class RecurringInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $required = $this->isUpdate() ? 'sometimes' : 'required';

        return [
            'company_id' => [$required, Rule::exists('companies', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => [$required, Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'emission_point_id' => [$required, Rule::exists('emission_points', 'id')->where('tenant_id', $tenantId)],
            'customer_id' => [$required, Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'name' => ['nullable', 'string', 'max:120'],
            'frequency' => [$required, 'string', Rule::in(RecurringInvoice::FREQUENCIES)],
            'start_date' => [$required, 'date'],
            'next_issue_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'paused', 'cancelled'])],
            'items' => [$required, 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.main_code' => ['nullable', 'string', 'max:50'],
            'items.*.aux_code' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage_code' => ['nullable', 'string', 'max:5'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*.code' => ['required_with:payment_methods', 'string', 'max:5'],
            'payment_methods.*.term' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'payment_methods.*.time_unit' => ['nullable', 'string', 'max:10'],
            'additional_info' => ['nullable', 'array'],
            'additional_info.*' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'max_issues' => ['nullable', 'integer', 'min:1'],
            'notify_before_issue' => ['nullable', 'boolean'],
            'notify_days_before' => ['nullable', 'integer', 'min:0', 'max:30'],
            'auto_send' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var RecurringInvoice|null $current */
            $current = $this->route('recurring_invoice');

            $companyId = (int) ($this->input('company_id') ?? $current?->company_id);
            $branchId = (int) ($this->input('branch_id') ?? $current?->branch_id);
            $emissionPointId = (int) ($this->input('emission_point_id') ?? $current?->emission_point_id);

            if ($branchId && $companyId) {
                $ok = Branch::where('id', $branchId)->where('company_id', $companyId)->exists();
                if (! $ok) {
                    $validator->errors()->add('branch_id', 'El establecimiento no pertenece a la empresa seleccionada.');
                }
            }

            if ($emissionPointId && $branchId) {
                $ok = EmissionPoint::where('id', $emissionPointId)->where('branch_id', $branchId)->exists();
                if (! $ok) {
                    $validator->errors()->add('emission_point_id', 'El punto de emisión no pertenece al establecimiento seleccionado.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'branch_id.required' => 'El establecimiento es requerido.',
            'emission_point_id.required' => 'El punto de emisión es requerido.',
            'customer_id.required' => 'El cliente es requerido.',
            'frequency.required' => 'La frecuencia es requerida.',
            'frequency.in' => 'Frecuencia inválida. Usa: '.implode(', ', RecurringInvoice::FREQUENCIES).'.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            'items.required' => 'Debe incluir al menos un ítem.',
            'items.min' => 'Debe incluir al menos un ítem.',
            'items.*.description.required' => 'La descripción es requerida para cada ítem.',
            'items.*.quantity.required' => 'La cantidad es requerida para cada ítem.',
            'items.*.unit_price.required' => 'El precio unitario es requerido para cada ítem.',
        ];
    }

    private function isUpdate(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }
}
