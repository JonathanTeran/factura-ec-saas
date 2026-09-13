<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica integraciones externas (/api/v1/ext) con una API key de la tabla
 * api_keys. Orden de comprobaciones: llave → tenant accesible → suscripción
 * vigente → plan con API → usuario que actúa.
 *
 * Deja la llave y el tenant en los atributos de la petición (nunca en el
 * input, para no contaminar los FormRequest) y fija request->user() con el
 * owner del tenant (o un admin activo), de modo que los controladores del
 * panel, que filtran por user()->tenant_id, funcionan sin cambios.
 */
class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $this->extractKey($request);

        if ($plain === null) {
            return ApiError::json(
                'missing_api_key',
                'Envía tu API key en la cabecera Authorization: Bearer fec_… (o X-API-Key).',
                401
            );
        }

        $apiKey = ApiKey::findByPlainKey($plain);

        if (! $apiKey || ! $apiKey->is_active) {
            return ApiError::json('invalid_api_key', 'API key inválida o desactivada.', 401);
        }

        if ($apiKey->isExpired()) {
            return ApiError::json(
                'expired_api_key',
                'La API key caducó. Genera una nueva desde Configuración → API e integraciones.',
                401
            );
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::withoutGlobalScopes()
            ->with('currentPlan')
            ->find($apiKey->tenant_id);

        if (! $tenant || ! $tenant->isAccessible()) {
            return ApiError::json('tenant_inactive', 'La cuenta está suspendida o inactiva.', 403);
        }

        $upgradeUrl = rtrim((string) config('app.url'), '/').'/settings/subscription';

        if (! $tenant->activeSubscription()->exists()) {
            return ApiError::json(
                'subscription_required',
                'La cuenta no tiene una suscripción vigente.',
                403,
                ['upgrade_url' => $upgradeUrl]
            );
        }

        if (! $tenant->hasFeature('api_access')) {
            return ApiError::json(
                'api_access_not_allowed',
                'Tu plan no incluye acceso a la API. Disponible desde el plan Negocio.',
                403,
                ['upgrade_url' => $upgradeUrl]
            );
        }

        $actor = $this->resolveActor($tenant);

        if (! $actor) {
            return ApiError::json('tenant_inactive', 'La cuenta no tiene un usuario administrador activo.', 403);
        }

        $apiKey->setRelation('tenant', $tenant);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_tenant', $tenant);
        $request->setUserResolver(fn () => $actor);

        $apiKey->touchUsage($request->ip());

        return $next($request);
    }

    /**
     * Solo cabeceras: `Authorization: Bearer fec_…` o `X-API-Key`. Nunca la
     * query string (queda en logs de proxies y navegadores).
     */
    private function extractKey(Request $request): ?string
    {
        $bearer = $request->bearerToken();

        if (is_string($bearer) && str_starts_with($bearer, 'fec_')) {
            return trim($bearer);
        }

        $header = $request->header('X-API-Key');

        if (is_string($header) && trim($header) !== '') {
            return trim($header);
        }

        return null;
    }

    /** Owner del tenant; si no existe o está inactivo, el primer admin activo. */
    private function resolveActor(Tenant $tenant): ?User
    {
        $owner = $tenant->owner;

        if ($owner && $owner->is_active && (int) $owner->tenant_id === (int) $tenant->id) {
            return $owner;
        }

        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('role', [UserRole::TENANT_OWNER->value, UserRole::ADMIN->value])
            ->orderBy('id')
            ->first();
    }
}
