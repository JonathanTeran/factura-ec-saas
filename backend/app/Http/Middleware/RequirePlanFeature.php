<?php

namespace App\Http\Middleware;

use App\Exceptions\FeatureNotAvailableException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a una funcionalidad cuando el plan del tenant no la
 * incluye. Uso: ->middleware('plan.feature:inventory').
 *
 * Lanza FeatureNotAvailableException (403 en API, redirección a facturación
 * en web) que ya tiene su handler registrado en bootstrap/app.php.
 */
class RequirePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $request->user()?->tenant;

        // Sin tenant (super admin, rutas sin auth) no se restringe aquí.
        if ($tenant && ! $tenant->hasFeature($feature)) {
            throw new FeatureNotAvailableException($feature);
        }

        return $next($request);
    }
}
