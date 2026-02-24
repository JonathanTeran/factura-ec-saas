<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The model class used for tenants in the application.
    |
    */

    'tenant_model' => App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Configuration for how tenants are identified in the system.
    |
    */

    'identification' => [
        'column' => 'tenant_id',
        'header' => 'X-Tenant-ID',
        'query_param' => 'tenant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation Strategy
    |--------------------------------------------------------------------------
    |
    | The strategy used for tenant data isolation.
    | Supported: "row_level" (single database with tenant_id column)
    |
    */

    'isolation_strategy' => 'row_level',

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    |
    | The bootstrappers that should run when a tenant is identified.
    | These set up the tenant context for the current request.
    |
    */

    'bootstrappers' => [
        // Add custom bootstrappers here
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware configuration for tenant identification.
    |
    */

    'middleware' => [
        'identify' => App\Http\Middleware\IdentifyTenant::class,
        'enforce' => App\Http\Middleware\EnforceTenancy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Route configuration for tenant access.
    |
    */

    'routes' => [
        'tenant_prefix' => 'panel',
        'api_prefix' => 'api/v1',
        'admin_prefix' => 'admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models with Tenant Scope
    |--------------------------------------------------------------------------
    |
    | Models that should automatically apply tenant scoping.
    | These models must use the BelongsToTenant trait.
    |
    */

    'scoped_models' => [
        App\Models\Tenant\Company::class,
        App\Models\Tenant\Branch::class,
        App\Models\Tenant\EmissionPoint::class,
        App\Models\Tenant\Customer::class,
        App\Models\Tenant\Product::class,
        App\Models\Tenant\Category::class,
        App\Models\Tenant\InventoryMovement::class,
        App\Models\SRI\ElectronicDocument::class,
        App\Models\SRI\DocumentItem::class,
        App\Models\SRI\SequentialNumber::class,
        App\Models\SRI\WithholdingDetail::class,
        App\Models\Billing\Subscription::class,
        App\Models\Billing\Payment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant-Aware Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific caching.
    |
    */

    'cache' => [
        'prefix' => 'tenant',
        'ttl' => 3600,
        'tags' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific file storage.
    |
    */

    'storage' => [
        'disk' => 'tenant',
        'path_prefix' => 'tenants/{tenant_id}',
        'folders' => [
            'certificates',
            'logos',
            'documents',
            'exports',
            'imports',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-aware job queuing.
    |
    */

    'queue' => [
        'tenant_aware' => true,
        'connection' => env('QUEUE_CONNECTION', 'redis'),
        'queue_prefix' => 'tenant_{tenant_id}_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific sessions.
    |
    */

    'session' => [
        'tenant_aware' => true,
        'cookie_prefix' => 'tenant_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation
    |--------------------------------------------------------------------------
    |
    | Settings for tenant impersonation (super admin feature).
    |
    */

    'impersonation' => [
        'enabled' => true,
        'session_key' => 'impersonating_tenant',
        'original_tenant_key' => 'original_tenant',
        'allowed_roles' => ['super_admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Settings
    |--------------------------------------------------------------------------
    |
    | Default settings applied to new tenants.
    |
    */

    'defaults' => [
        'timezone' => 'America/Guayaquil',
        'locale' => 'es',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'currency' => 'USD',
        'decimal_separator' => '.',
        'thousands_separator' => ',',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Onboarding
    |--------------------------------------------------------------------------
    |
    | Settings for the tenant onboarding process.
    |
    */

    'onboarding' => [
        'steps' => [
            'company_info' => [
                'required' => true,
                'order' => 1,
            ],
            'certificate' => [
                'required' => true,
                'order' => 2,
            ],
            'branch' => [
                'required' => true,
                'order' => 3,
            ],
            'products' => [
                'required' => false,
                'order' => 4,
            ],
            'customers' => [
                'required' => false,
                'order' => 5,
            ],
        ],
        'skip_allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Deletion
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant deletion behavior.
    |
    */

    'deletion' => [
        'soft_delete' => true,
        'grace_period_days' => 30,
        'cascade_delete' => true,
        'backup_before_delete' => true,
        'notify_admin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Configuration for super admin access.
    |
    */

    'super_admin' => [
        'bypass_tenant_scope' => true,
        'access_all_tenants' => true,
        'email_domains' => explode(',', env('SUPER_ADMIN_DOMAINS', '')),
    ],

];
