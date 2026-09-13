<?php

namespace App\Http\Middleware;

use App\Models\Tenant\ApiKey;
use App\Support\ApiError;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límite de peticiones por minuto de la API de integración, por llave:
 * el menor entre el límite propio de la llave y el del plan
 * (ver ApiKey::PLAN_RATE_LIMITS). Debe ir después de `api.key`.
 */
class ApiRateLimiting
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey instanceof ApiKey) {
            // Falla cerrado: este middleware solo tiene sentido tras api.key.
            return ApiError::json('missing_api_key', 'Petición sin API key.', 401);
        }

        $bucket = 'api_rate:'.$apiKey->id;
        $max = $apiKey->effectiveRateLimit();

        if ($this->limiter->tooManyAttempts($bucket, $max)) {
            $retryAfter = max(1, $this->limiter->availableIn($bucket));

            return ApiError::json(
                'rate_limit_exceeded',
                "Demasiadas solicitudes. Intenta de nuevo en {$retryAfter} segundos.",
                429,
                ['retry_after' => $retryAfter]
            )->withHeaders([
                'X-RateLimit-Limit' => $max,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        $this->limiter->hit($bucket, 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $max);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $this->limiter->remaining($bucket, $max)));

        return $response;
    }
}
