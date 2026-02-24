<?php

namespace App\Http\Middleware;

use App\Models\Portal\CustomerPortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('portal.cookie_name', 'customer_portal_session');
        $sessionId = $request->cookie($cookieName);

        if (!$sessionId) {
            return $this->redirectToLogin($cookieName);
        }

        $session = CustomerPortalSession::find($sessionId);

        if (!$session || !$session->isValid()) {
            if ($session) {
                $session->delete();
            }
            return $this->redirectToLogin($cookieName);
        }

        // Actualizar actividad
        $session->touchActivity();

        // Poner sesion disponible en el request
        $request->attributes->set('portal_session', $session);
        app()->instance('portal.session', $session);

        // Compartir con vistas
        view()->share('portalSession', $session);

        return $next($request);
    }

    protected function redirectToLogin(string $cookieName): Response
    {
        return redirect()
            ->route('portal.login')
            ->withCookie(cookie()->forget($cookieName));
    }
}
