<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used.
     */
    'api_domain' => null,

    'info' => [
        /*
         * API version.
         */
        'version' => '1.0.0',

        /*
         * Description rendered on the docs page. Supports Markdown.
         */
        'description' => <<<'MD'
# AmePhia Facturación Electrónica API

API REST para el sistema de facturación electrónica del Ecuador, compatible con el SRI.

## Autenticación

Todas las rutas protegidas requieren un token Bearer de Sanctum. Obtén tu token mediante el endpoint `POST /api/v1/auth/login`.

```
Authorization: Bearer {tu-token}
```

## Tipos de Documento

| Código | Tipo |
|--------|------|
| 01 | Factura |
| 04 | Nota de Crédito |
| 05 | Nota de Débito |
| 06 | Guía de Remisión |
| 07 | Comprobante de Retención |

## Formato de Respuesta

Todas las respuestas siguen el formato:

```json
{
  "success": true,
  "message": "Descripción",
  "data": {}
}
```

## Errores

```json
{
  "success": false,
  "message": "Descripción del error",
  "errors": []
}
```

## Límites

Los límites de documentos y usuarios dependen del plan de suscripción activo.
MD,
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Hide the `Try it` feature. Enabled by default.
         */
        'try_it_credentials_policy' => 'same-origin',
    ],

    /*
     * Define the list of servers for the API docs.
     */
    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
