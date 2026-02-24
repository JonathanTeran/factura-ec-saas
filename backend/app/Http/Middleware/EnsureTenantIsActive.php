<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        // Super admins bypass tenant check
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Cuenta no asociada a ninguna empresa.'], 403);
            }
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta no está asociada a ninguna empresa.');
        }

        if (!$tenant->isAccessible()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Cuenta suspendida o inactiva.'], 403);
            }
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta de empresa está suspendida o inactiva. Contacta soporte.');
        }

        return $next($request);
    }
}
