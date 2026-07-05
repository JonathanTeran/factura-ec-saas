<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Correo de recuperación de clave, pero ENCOLADO (lo procesa Horizon).
 * El envío síncrono en php-fpm colgaba/crasheaba el worker; encolarlo lo
 * evita y es la práctica correcta (no bloquear la respuesta HTTP).
 *
 * Hereda el enlace personalizado (ResetPassword::createUrlUsing) definido en
 * FortifyServiceProvider, que apunta a la página de reset del frontend.
 */
class QueuedResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;
}
