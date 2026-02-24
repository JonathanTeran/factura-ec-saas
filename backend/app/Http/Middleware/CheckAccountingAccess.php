<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin bypasses
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user || !$user->tenant) {
            abort(403, 'Acceso denegado al modulo de contabilidad.');
        }

        if (!$user->tenant->has_accounting) {
            abort(403, 'El modulo de contabilidad no esta habilitado para su cuenta.');
        }

        if (!$user->hasPermission('view_accounting')) {
            abort(403, 'No tiene permisos para acceder al modulo de contabilidad.');
        }

        return $next($request);
    }
}
