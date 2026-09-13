<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $grantAccess = (bool) ($data['grant_api_access'] ?? false);
        unset($data['grant_api_access']);

        $tenant = Tenant::with('currentPlan')->findOrFail($data['tenant_id']);

        if ($grantAccess && ! $tenant->has_api_access) {
            $tenant->update(['has_api_access' => true]);
            \App\Services\Cache\TenantCacheService::invalidateTenant($tenant->id);
        }

        $plain = ApiKey::generatePlainKey();
        $planLimit = ApiKey::rateLimitForPlanSlug($tenant->currentPlan?->slug);
        $requested = (int) ($data['rate_limit_per_minute'] ?? 0) ?: $planLimit;

        $key = ApiKey::create([
            'tenant_id' => $tenant->id,
            'created_by' => auth()->id(),
            'name' => $data['name'],
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixFrom($plain),
            'permissions' => array_values(array_intersect(array_keys(ApiKey::SCOPES), (array) ($data['permissions'] ?? []))),
            'rate_limit_per_minute' => max(1, $requested),
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => true,
        ]);

        // La llave en claro se muestra una sola vez en la vista de detalle.
        session()->put(ApiKeyResource::plainKeySessionKey($key), $plain);

        return $key;
    }

    protected function getRedirectUrl(): string
    {
        return ApiKeyResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Integración creada. Copia la llave ahora: no volverá a mostrarse.';
    }
}
