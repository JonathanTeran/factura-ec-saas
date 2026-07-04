<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de bienvenida al nuevo usuario tras registrarse, con los pasos
 * pendientes para poder empezar a facturar (configuración asistida).
 */
class WelcomeTenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        // Encolar solo después de confirmar la transacción de registro.
        $this->afterCommit = true;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $onboardingUrl = rtrim((string) config('app.url'), '/') . '/onboarding';

        return (new MailMessage)
            ->subject('¡Bienvenido a AmePhia Facturación!')
            ->greeting('Hola ' . $this->user->name . ' 👋')
            ->line('Tu cuenta ya está creada. Para empezar a emitir comprobantes electrónicos, completá esta configuración asistida (te toma unos minutos):')
            ->line('1️⃣  **Datos de tu empresa** — RUC, razón social, dirección y régimen tributario.')
            ->line('2️⃣  **Firma electrónica (.p12)** — subí tu certificado del BCE, Security Data o ANF. No vendemos certificados: usás el tuyo y nosotros firmamos por vos.')
            ->line('3️⃣  **Establecimiento y punto de emisión** — para que tus facturas cumplan con el SRI.')
            ->action('Completar mi configuración', $onboardingUrl)
            ->line('Una vez completado, ya podés emitir tu primera factura. Si tenés dudas, respondé a este correo y te ayudamos.')
            ->salutation('El equipo de AmePhia');
    }
}
