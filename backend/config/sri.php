<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SRI Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the SRI (Servicio de Rentas Internas) environment settings.
    | Environment 1 = Testing/Pruebas, Environment 2 = Production/Producción
    |
    */

    'default_environment' => env('SRI_ENVIRONMENT', '1'),

    /*
    |--------------------------------------------------------------------------
    | SRI Web Service URLs
    |--------------------------------------------------------------------------
    |
    | The official SRI web service endpoints for both testing and production
    | environments. These are used for document reception and authorization.
    |
    */

    'wsdl' => [
        'reception' => [
            '1' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            '2' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
        ],
        'authorization' => [
            '1' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            '2' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    |
    | SRI document type codes as defined in the technical documentation.
    |
    */

    'document_types' => [
        '01' => 'Factura',
        '04' => 'Nota de Crédito',
        '05' => 'Nota de Débito',
        '06' => 'Guía de Remisión',
        '07' => 'Comprobante de Retención',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identification Types
    |--------------------------------------------------------------------------
    |
    | SRI identification type codes for customers and suppliers.
    |
    */

    'identification_types' => [
        '04' => 'RUC',
        '05' => 'Cédula',
        '06' => 'Pasaporte',
        '07' => 'Consumidor Final',
        '08' => 'Identificación del Exterior',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Types
    |--------------------------------------------------------------------------
    |
    | SRI tax type codes used in electronic documents.
    |
    */

    'tax_types' => [
        '2' => 'IVA',
        '3' => 'ICE',
        '5' => 'IRBPNR',
    ],

    /*
    |--------------------------------------------------------------------------
    | IVA Tax Percentages
    |--------------------------------------------------------------------------
    |
    | SRI IVA percentage codes and their corresponding rates.
    |
    */

    'iva_percentages' => [
        '0' => ['name' => '0%', 'rate' => 0],
        '2' => ['name' => '12%', 'rate' => 12],
        '3' => ['name' => '14%', 'rate' => 14],
        '4' => ['name' => '15%', 'rate' => 15],
        '5' => ['name' => '5%', 'rate' => 5],
        '6' => ['name' => 'No Objeto de Impuesto', 'rate' => 0],
        '7' => ['name' => 'Exento de IVA', 'rate' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | SRI payment method codes for electronic documents.
    |
    */

    'payment_methods' => [
        '01' => 'Sin utilización del sistema financiero',
        '15' => 'Compensación de deudas',
        '16' => 'Tarjeta de débito',
        '17' => 'Dinero electrónico',
        '18' => 'Tarjeta prepago',
        '19' => 'Tarjeta de crédito',
        '20' => 'Otros con utilización del sistema financiero',
        '21' => 'Endoso de títulos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Codes
    |--------------------------------------------------------------------------
    |
    | Common retention codes for income tax and VAT.
    |
    */

    'retention_codes' => [
        'renta' => [
            '303' => ['percentage' => 10, 'name' => 'Honorarios profesionales y demás pagos por servicios relacionados con el título profesional'],
            '304' => ['percentage' => 8, 'name' => 'Servicios predomina el intelecto no relacionados con el título profesional'],
            '307' => ['percentage' => 2, 'name' => 'Servicios predomina la mano de obra'],
            '308' => ['percentage' => 2, 'name' => 'Servicios entre sociedades'],
            '309' => ['percentage' => 1, 'name' => 'Servicios publicidad y comunicación'],
            '310' => ['percentage' => 1, 'name' => 'Transporte privado de pasajeros o servicio público o privado de carga'],
            '312' => ['percentage' => 1, 'name' => 'Transferencia de bienes muebles de naturaleza corporal'],
            '319' => ['percentage' => 1, 'name' => 'Arrendamiento mercantil'],
            '320' => ['percentage' => 8, 'name' => 'Arrendamiento bienes inmuebles'],
            '322' => ['percentage' => 1.75, 'name' => 'Seguros y reaseguros'],
            '323' => ['percentage' => 2, 'name' => 'Por rendimientos financieros'],
            '332' => ['percentage' => 0, 'name' => 'Otras compras de bienes y servicios no sujetas a retención'],
            '341' => ['percentage' => 1, 'name' => 'Otras retenciones aplicables 1%'],
            '342' => ['percentage' => 2, 'name' => 'Otras retenciones aplicables 2%'],
            '343' => ['percentage' => 8, 'name' => 'Otras retenciones aplicables 8%'],
            '344' => ['percentage' => 25, 'name' => 'Otras retenciones aplicables 25%'],
        ],
        'iva' => [
            '721' => ['percentage' => 30, 'name' => 'Retención 30% del IVA'],
            '723' => ['percentage' => 70, 'name' => 'Retención 70% del IVA'],
            '725' => ['percentage' => 100, 'name' => 'Retención 100% del IVA'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | XML Settings
    |--------------------------------------------------------------------------
    |
    | Settings for XML document generation.
    |
    */

    'xml' => [
        'version' => '1.0',
        'encoding' => 'UTF-8',
        'standalone' => 'yes',
        'schema_version' => '1.1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Key Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for generating SRI access keys (claves de acceso).
    |
    */

    'access_key' => [
        'length' => 49,
        'check_digit_modulo' => 11,
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Settings
    |--------------------------------------------------------------------------
    |
    | Settings for digital certificate handling.
    |
    */

    'certificate' => [
        'allowed_extensions' => ['p12', 'pfx'],
        'max_size_kb' => 5120,
        'storage_disk' => 'local',
        'storage_path' => 'certificates',
        'encryption_algorithm' => 'AES-256-CBC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Storage
    |--------------------------------------------------------------------------
    |
    | Settings for storing generated XML documents.
    |
    */

    'storage' => [
        'disk' => env('SRI_STORAGE_DISK', 'local'),
        'path' => 'sri/documents',
        'signed_path' => 'sri/signed',
        'authorized_path' => 'sri/authorized',
        'pdf_path' => 'sri/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Settings for document processing and retries.
    |
    */

    'processing' => [
        'max_retries' => 3,
        'retry_delay_seconds' => 30,
        'authorization_check_delay' => 5,
        'max_authorization_checks' => 10,
        'timeout_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Consumidor Final
    |--------------------------------------------------------------------------
    |
    | Default values for "Consumidor Final" (Final Consumer) transactions.
    |
    */

    'consumidor_final' => [
        'identification' => '9999999999999',
        'identification_type' => '07',
        'business_name' => 'CONSUMIDOR FINAL',
        'address' => 'S/N',
        'max_amount' => 50.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    |
    | Settings for document email notifications.
    |
    */

    'notifications' => [
        'send_on_authorization' => true,
        'attach_xml' => true,
        'attach_pdf' => true,
        'from_name' => env('SRI_MAIL_FROM_NAME', config('app.name')),
        'from_email' => env('SRI_MAIL_FROM_EMAIL', config('mail.from.address')),
    ],

];
