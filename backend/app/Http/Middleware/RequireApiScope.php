<?php

namespace App\Http\Middleware;

use App\Models\Tenant\ApiKey;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que la API key tenga un alcance. Uso: ->middleware('api.scope:documents:write').
 * Debe ir después de `api.key`.
 */
class RequireApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey instanceof ApiKey) {
            return ApiError::json('missing_api_key', 'Petición sin API key.', 401);
        }

        if (! $apiKey->hasScope($scope)) {
            return ApiError::json(
                'insufficient_scope',
                "Esta API key no tiene el alcance {$scope}.",
                403,
                ['required_scope' => $scope, 'key_scopes' => $apiKey->scopes()]
            );
        }

        return $next($request);
    }
}
