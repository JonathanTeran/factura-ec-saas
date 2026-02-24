<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'company_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:companies,id',
            ],
            'certificate_file' => [
                $isUpdate ? 'sometimes' : 'required',
                'file',
                'mimes:p12,pfx',
                'max:5120', // 5MB max
            ],
            'certificate_password' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'issued_to' => ['nullable', 'string', 'max:255'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'certificate_file.required' => 'El archivo del certificado es requerido.',
            'certificate_file.mimes' => 'El certificado debe ser un archivo .p12 o .pfx.',
            'certificate_file.max' => 'El certificado no debe exceder 5MB.',
            'certificate_password.required' => 'La contraseña del certificado es requerida.',
            'valid_until.after' => 'La fecha de vencimiento debe ser posterior a la fecha de inicio.',
        ];
    }
}
