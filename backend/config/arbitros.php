<?php

/**
 * Configuración del vertical de árbitros. Ver docs/arbitros-vertical-spec.md.
 */
return [
    /*
     * Receptor por defecto de las facturas del árbitro (la FEF).
     * El RUC exacto está pendiente de confirmar (§9 del spec: nacional vs.
     * asociación provincial). Mientras no se configure, el módulo NO crea el
     * cliente automáticamente para no registrar datos fiscales inventados.
     */
    'fef' => [
        'ruc' => env('FEF_RUC'),
        'business_name' => env('FEF_BUSINESS_NAME', 'FEDERACION ECUATORIANA DE FUTBOL'),
        'email' => env('FEF_EMAIL'),
    ],

    /* Ventana de recepción de facturas de la FEF (§5.2). Configurable. */
    'invoice_window' => [
        'start_day' => (int) env('FEF_WINDOW_START_DAY', 1),
        'end_day' => (int) env('FEF_WINDOW_END_DAY', 20),
    ],

    /* API pública FEF para ingesta (§6, §13). */
    'api' => [
        'base_url' => env('FEF_API_BASE', 'https://apiweb.fef.ec/api/public'),
        'timeout' => (int) env('FEF_API_TIMEOUT', 30),
    ],

    /* Auto-matching: ventana de partidos hacia atrás a considerar. */
    'matching' => [
        'since_days' => (int) env('FEF_MATCHING_SINCE_DAYS', 60),
    ],
];
