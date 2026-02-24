<?php

namespace App\Providers;

use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Certificate;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Product;
use App\Models\User;
use App\Policies\CertificatePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmissionPointPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Customer::class => CustomerPolicy::class,
        Product::class => ProductPolicy::class,
        ElectronicDocument::class => DocumentPolicy::class,
        Company::class => CompanyPolicy::class,
        Certificate::class => CertificatePolicy::class,
        EmissionPoint::class => EmissionPointPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates
        Gate::define('access-admin', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('access-tenant-panel', function (User $user) {
            return $user->tenant_id !== null && $user->is_active;
        });

        Gate::define('access-api', function (User $user) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->tenant_id !== null
                && $user->is_active
                && $user->tenant->has_api_access;
        });

        // Permission-based gates
        $this->registerPermissionGates();
    }

    protected function registerPermissionGates(): void
    {
        $permissions = [
            // Customer permissions
            'manage_customers',
            'view_customers',

            // Product permissions
            'manage_products',
            'view_products',
            'manage_inventory',

            // Document permissions
            'create_documents',
            'edit_documents',
            'delete_documents',
            'sign_documents',
            'send_documents',
            'void_documents',
            'view_documents',

            // Company permissions
            'manage_companies',
            'manage_certificates',
            'view_companies',

            // User permissions
            'manage_users',
            'view_users',

            // Report permissions
            'view_reports',
            'export_reports',

            // Settings permissions
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                if ($user->isSuperAdmin()) {
                    return true;
                }

                return $user->hasPermission($permission);
            });
        }
    }
}
