<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Autenticación del panel /admin. Si la sesión web pertenece a un usuario
 * que NO puede entrar (p. ej. quedó logueado como dueño de un tenant tras
 * "Impersonar"), en vez del 403 "No tienes acceso a esta página" se cierra
 * esa sesión y se lleva al login del panel para entrar como super admin.
 *
 * Reemplaza a Filament\Http\Middleware\Authenticate (misma prioridad de
 * middleware: un middleware aparte corría DESPUÉS del abort(403)).
 */
class AuthenticatePanel extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();
        $panel = Filament::getCurrentPanel();

        if ($guard->check() && $panel) {
            $user = $guard->user();

            if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Notification::make()
                    ->title('Esa sesión era de una cuenta de empresa')
                    ->body('Ingresa con tu cuenta de super administrador para usar el panel de administración.')
                    ->warning()
                    ->send();

                throw new HttpResponseException(redirect()->to($panel->getLoginUrl() ?? '/admin/login'));
            }
        }

        parent::authenticate($request, $guards);
    }
}
