<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Rate limiting por tenant para la API.
     * Los límites dependen del plan contratado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $user?->tenant ?? $request->get('tenant');

        if (!$tenant) {
            return $next($request);
        }

        $key = 'api_rate_limit:' . $tenant->id;

        // Límites por plan
        $maxAttempts = $this->getMaxAttempts($tenant);
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Demasiadas solicitudes. Intenta de nuevo en ' . $retryAfter . ' segundos.',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->remaining($key, $maxAttempts),
        ]);
    }

    /**
     * Obtiene el límite de requests por minuto según el plan.
     */
    protected function getMaxAttempts($tenant): int
    {
        $plan = $tenant->currentPlan;

        if (!$plan) {
            return 30; // Plan gratis
        }

        // Configurar según el tipo de plan
        return match (true) {
            $plan->slug === 'empresarial' => 300,
            $plan->slug === 'profesional' => 120,
            $plan->slug === 'emprendedor' => 60,
            default => 30,
        };
    }
}
