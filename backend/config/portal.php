<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */

    'token_expiry_hours' => env('PORTAL_TOKEN_EXPIRY_HOURS', 24),

    'max_magic_link_requests_per_hour' => env('PORTAL_MAX_REQUESTS_PER_HOUR', 3),

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */

    'session_expiry_days' => env('PORTAL_SESSION_EXPIRY_DAYS', 7),

    'session_inactivity_minutes' => env('PORTAL_SESSION_INACTIVITY_MINUTES', 120),

    'cookie_name' => env('PORTAL_COOKIE_NAME', 'customer_portal_session'),

    /*
    |--------------------------------------------------------------------------
    | Display Configuration
    |--------------------------------------------------------------------------
    */

    'documents_per_page' => 15,

    'show_only_authorized_documents' => true,

];
