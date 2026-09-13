<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Variables comunes de las páginas de error (errors::*): URLs de inicio,
 * login y panel según la zona (admin de Filament o panel Next.js) y datos
 * de contacto de soporte. Se registra como composer porque las secciones de
 * una vista hija se evalúan antes que el @php del layout.
 */
class ErrorPageComposer
{
    public function compose(View $view): void
    {
        $frontendUrl = rtrim((string) config('support.frontend_url', config('app.url')), '/');
        $whatsapp = preg_replace('/\D/', '', (string) config('support.whatsapp'));
        $isAdminArea = request()->is('admin', 'admin/*');

        $view->with([
            'frontendUrl' => $frontendUrl,
            'supportEmail' => (string) config('support.email'),
            'supportWhatsapp' => $whatsapp,
            'whatsappPretty' => $whatsapp !== '' ? self::prettyPhone($whatsapp) : null,
            'isAdminArea' => $isAdminArea,
            'loginUrl' => $isAdminArea ? url('/admin/login') : $frontendUrl.'/login',
            'panelUrl' => $isAdminArea ? url('/admin') : $frontendUrl.'/dashboard',
            'homeUrl' => $frontendUrl.'/',
        ]);
    }

    /** 13347324056 → +1 334 732 4056 */
    private static function prettyPhone(string $digits): string
    {
        if (strlen($digits) === 11) {
            return sprintf('+%s %s %s %s', $digits[0], substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
        }

        return '+'.$digits;
    }
}
