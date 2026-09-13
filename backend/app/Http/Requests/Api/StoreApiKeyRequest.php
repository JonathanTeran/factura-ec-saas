<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use App\Models\Tenant\ApiKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
{
    /** Solo el propietario o un administrador del tenant gestiona llaves. */
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, [UserRole::TENANT_OWNER, UserRole::ADMIN], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(array_merge(['*'], array_keys(ApiKey::SCOPES)))],
            'expires_in_days' => ['nullable', 'integer', Rule::in([30, 90, 365])],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ponle un nombre a la llave (ej. "Tienda en línea").',
            'scopes.required' => 'Elige al menos un alcance.',
            'scopes.*.in' => 'Alcance no válido.',
            'expires_in_days.in' => 'La caducidad debe ser 30, 90 o 365 días.',
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Solo el propietario o un administrador puede gestionar las llaves de API.'
        );
    }
}
