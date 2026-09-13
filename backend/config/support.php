<?php

/*
|--------------------------------------------------------------------------
| Contacto de soporte y URLs públicas
|--------------------------------------------------------------------------
| Usado por las páginas de error y los correos. Las vistas nunca deben
| llamar a env() directamente (se pierde con config:cache).
*/

return [
    'email' => env('LANDING_CONTACT_EMAIL', 'info@amephia.com'),

    // Solo dígitos, con código de país (wa.me).
    'whatsapp' => env('LANDING_WHATSAPP', '13347324056'),

    // Panel Next.js (landing, /login, /dashboard). Normalmente igual a APP_URL.
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'https://facturon.ec')),

    'company' => 'AmePhia Systems Inc.',
    'company_url' => 'https://amephia.com',
];
