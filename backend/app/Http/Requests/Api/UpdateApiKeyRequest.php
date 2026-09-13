<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use App\Models\Tenant\ApiKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, [UserRole::TENANT_OWNER, UserRole::ADMIN], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'scopes' => ['sometimes', 'required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(array_merge(['*'], array_keys(ApiKey::SCOPES)))],
            'rate_limit_per_minute' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Solo el propietario o un administrador puede gestionar las llaves de API.'
        );
    }
}
