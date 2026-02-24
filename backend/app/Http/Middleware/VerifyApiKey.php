<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Verifica que la petición contenga una API Key válida.
     * Usado para integraciones externas que no usan tokens de usuario.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key no proporcionada.',
                'error' => 'missing_api_key',
            ], 401);
        }

        // Buscar el tenant por API key
        $tenant = Tenant::where('api_key', hash('sha256', $apiKey))
            ->where('api_key_enabled', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'API Key inválida.',
                'error' => 'invalid_api_key',
            ], 401);
        }

        if (!$tenant->isAccessible()) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta suspendida o inactiva.',
                'error' => 'tenant_inactive',
            ], 403);
        }

        // Verificar si el plan permite acceso API
        if (!$tenant->currentPlan?->has_api_access) {
            return response()->json([
                'success' => false,
                'message' => 'Tu plan no incluye acceso API.',
                'error' => 'api_access_not_allowed',
            ], 403);
        }

        // Adjuntar el tenant a la request para uso posterior
        $request->merge(['tenant' => $tenant]);
        $request->setUserResolver(fn() => $tenant->owner);

        // Actualizar último uso
        $tenant->update(['api_last_used_at' => now()]);

        return $next($request);
    }
}
