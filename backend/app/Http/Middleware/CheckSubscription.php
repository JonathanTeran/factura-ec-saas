<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Verifica que el tenant tenga una suscripción activa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        // Super admins no requieren suscripción
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return $this->noTenant($request);
        }

        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return $this->noSubscription($request);
        }

        // Verificar si está en periodo de gracia
        if ($subscription->isPastDue()) {
            // Permitir acceso limitado durante periodo de gracia (7 días)
            $gracePeriodDays = 7;
            $daysOverdue = $subscription->ends_at?->diffInDays(now()) ?? 0;

            if ($daysOverdue > $gracePeriodDays) {
                return $this->subscriptionExpired($request);
            }

            // Agregar advertencia en el header
            $request->headers->set('X-Subscription-Warning', 'past_due');
        }

        return $next($request);
    }

    protected function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->route('login');
    }

    protected function noTenant(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta no está asociada a ninguna empresa.',
                'error' => 'no_tenant',
            ], 403);
        }

        return redirect()->route('panel.dashboard')
            ->with('error', 'Tu cuenta no está configurada correctamente.');
    }

    protected function noSubscription(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una suscripción activa.',
                'error' => 'no_subscription',
                'action' => 'subscribe',
            ], 402);
        }

        return redirect()->route('panel.settings.billing')
            ->with('warning', 'Necesitas activar una suscripción para continuar.');
    }

    protected function subscriptionExpired(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu suscripción ha expirado.',
                'error' => 'subscription_expired',
                'action' => 'renew',
            ], 402);
        }

        return redirect()->route('panel.settings.billing')
            ->with('error', 'Tu suscripción ha expirado. Renuévala para continuar.');
    }
}
