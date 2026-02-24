<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verifica que el usuario tenga el permiso especificado.
     *
     * Uso en rutas:
     * Route::get('/documents', ...)->middleware('permission:documents.view');
     * Route::post('/documents', ...)->middleware('permission:documents.create');
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado.',
                    'error' => 'unauthenticated',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Super admins tienen todos los permisos
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Verificar el permiso
        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta acción.',
                    'error' => 'permission_denied',
                    'required_permission' => $permission,
                ], 403);
            }

            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
