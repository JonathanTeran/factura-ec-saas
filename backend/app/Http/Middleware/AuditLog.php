<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLog
{
    /**
     * Registra las acciones importantes para auditoría.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo auditar métodos que modifican datos
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // No auditar rutas excluidas
        if ($this->isExcluded($request)) {
            return $response;
        }

        $this->logAction($request, $response);

        return $response;
    }

    protected function logAction(Request $request, Response $response): void
    {
        $user = $request->user();

        $logData = [
            'timestamp' => now()->toISOString(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'tenant_id' => $user?->tenant_id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'request_id' => $request->header('X-Request-ID'),
        ];

        // No loguear datos sensibles
        $safeInput = $this->sanitizeInput($request->except([
            'password',
            'password_confirmation',
            'current_password',
            'certificate_password',
            'sri_password',
            'card_token',
            'api_key',
        ]));

        if (!empty($safeInput)) {
            $logData['input'] = $safeInput;
        }

        Log::channel('audit')->info('API Action', $logData);
    }

    protected function sanitizeInput(array $input): array
    {
        // Limitar profundidad y tamaño
        return collect($input)
            ->take(20)
            ->map(function ($value) {
                if (is_array($value)) {
                    return '[array]';
                }
                if (is_string($value) && strlen($value) > 200) {
                    return substr($value, 0, 200) . '...';
                }
                return $value;
            })
            ->toArray();
    }

    protected function isExcluded(Request $request): bool
    {
        $excludedRoutes = [
            'sanctum.csrf-cookie',
            'login',
            'logout',
            'password.*',
        ];

        $routeName = $request->route()?->getName();

        foreach ($excludedRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
