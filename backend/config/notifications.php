<?php

$adminRecipients = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'ADMIN_NOTIFICATION_EMAILS',
        // Compatibilidad con la variable anterior + valores por defecto.
        env('REGISTRATION_NOTIFICATION_EMAILS', 'jo.teran3@gmail.com,jo-teran@hotmail.com')
    )),
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Correos de administración
    |--------------------------------------------------------------------------
    |
    | Reciben TODOS los avisos importantes de la plataforma: nuevos registros,
    | pagos por transferencia pendientes de aprobar, etc. Configurable por env
    | (ADMIN_NOTIFICATION_EMAILS, separados por coma) sin tocar código.
    |
    */

    'admin_recipients' => $adminRecipients,

    // Alias mantenido por compatibilidad; apunta a los mismos correos.
    'registration_recipients' => $adminRecipients,

];
