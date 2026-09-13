<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreApiKeyRequest;
use App\Http\Requests\Api\UpdateApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de llaves de la API de integración desde el panel
 * (Configuración → API e integraciones). Rutas detrás de
 * `plan.feature:api_access`: sin el plan adecuado responden 403
 * `feature_not_available`.
 *
 * @tags API keys
 */
class ApiKeyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $keys = ApiKey::where('tenant_id', $tenant->id)
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'api_keys' => ApiKeyResource::collection($keys),
            'scopes' => ApiKey::SCOPES,
            'limits' => $this->limitsFor($tenant),
            'base_url' => rtrim((string) config('app.url'), '/').'/api/v1/ext',
            'docs_url' => rtrim((string) config('app.url'), '/').'/docs/api',
        ]);
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $limits = $this->limitsFor($tenant);

        $active = ApiKey::where('tenant_id', $tenant->id)->where('is_active', true)->count();

        if ($active >= ApiKey::MAX_ACTIVE_PER_TENANT) {
            return ApiError::json(
                'max_keys_reached',
                'Alcanzaste el máximo de '.ApiKey::MAX_ACTIVE_PER_TENANT.' llaves activas. Desactiva o elimina una para crear otra.',
                422,
                ['limit' => ApiKey::MAX_ACTIVE_PER_TENANT]
            );
        }

        $plain = ApiKey::generatePlainKey();
        $requestedLimit = (int) ($request->input('rate_limit_per_minute') ?: $limits['plan_rate_limit']);

        $key = ApiKey::create([
            'tenant_id' => $tenant->id,
            'created_by' => $request->user()->id,
            'name' => $request->input('name'),
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixFrom($plain),
            'permissions' => $this->normalizeScopes($request->input('scopes', [])),
            'rate_limit_per_minute' => max(1, min($requestedLimit, $limits['plan_rate_limit'])),
            'expires_at' => $request->filled('expires_in_days')
                ? now()->addDays((int) $request->input('expires_in_days'))
                : null,
            'is_active' => true,
        ]);

        return $this->created([
            'api_key' => new ApiKeyResource($key->load('creator:id,name')),
            'plain_key' => $plain,
        ], 'Guarda esta llave ahora: no volverá a mostrarse.');
    }

    public function update(UpdateApiKeyRequest $request, int $id): JsonResponse
    {
        $key = $this->findKey($request, $id);
        $limits = $this->limitsFor($request->user()->tenant);

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->input('name');
        }
        if ($request->has('scopes')) {
            $data['permissions'] = $this->normalizeScopes($request->input('scopes', []));
        }
        if ($request->has('rate_limit_per_minute')) {
            $requested = (int) ($request->input('rate_limit_per_minute') ?: $limits['plan_rate_limit']);
            $data['rate_limit_per_minute'] = max(1, min($requested, $limits['plan_rate_limit']));
        }
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $key->update($data);

        return $this->success([
            'api_key' => new ApiKeyResource($key->fresh()->load('creator:id,name')),
        ], 'Llave actualizada.');
    }

    /** Genera una credencial nueva; la anterior deja de funcionar de inmediato. */
    public function rotate(Request $request, int $id): JsonResponse
    {
        $this->authorizeManager($request);
        $key = $this->findKey($request, $id);

        $plain = $key->rotate();

        return $this->success([
            'api_key' => new ApiKeyResource($key->fresh()->load('creator:id,name')),
            'plain_key' => $plain,
        ], 'Llave rotada. Actualiza tus integraciones: la anterior ya no funciona.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeManager($request);
        $key = $this->findKey($request, $id);

        $key->delete();

        return $this->success(null, 'Llave eliminada.');
    }

    protected function findKey(Request $request, int $id): ApiKey
    {
        return ApiKey::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
    }

    protected function authorizeManager(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, [\App\Enums\UserRole::TENANT_OWNER, \App\Enums\UserRole::ADMIN], true)) {
            abort(403, 'Solo el propietario o un administrador puede gestionar las llaves de API.');
        }
    }

    /** @return array{max_keys:int, plan_rate_limit:int, plan_slug:?string} */
    protected function limitsFor(Tenant $tenant): array
    {
        $slug = $tenant->currentPlan?->slug;

        return [
            'max_keys' => ApiKey::MAX_ACTIVE_PER_TENANT,
            'plan_rate_limit' => ApiKey::rateLimitForPlanSlug($slug),
            'plan_slug' => $slug,
        ];
    }

    /** `*` absorbe al resto; siempre se devuelve una lista ordenada sin duplicados. */
    protected function normalizeScopes(array $scopes): array
    {
        $scopes = array_values(array_unique(array_filter($scopes, 'is_string')));

        if (in_array('*', $scopes, true)) {
            return ['*'];
        }

        sort($scopes);

        return $scopes;
    }
}
