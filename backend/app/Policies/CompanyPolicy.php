<?php

namespace App\Policies;

use App\Models\Tenant\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id;
    }

    public function create(User $user): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        if (!$user->can('manage_companies')) {
            return false;
        }

        $tenant = $user->tenant;
        $currentCount = $tenant->companies()->count();

        return $currentCount < $tenant->max_companies;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id
            && $user->can('manage_companies');
    }

    public function delete(User $user, Company $company): bool
    {
        if ($user->tenant_id !== $company->tenant_id) {
            return false;
        }

        if (!$user->can('manage_companies')) {
            return false;
        }

        $tenant = $user->tenant;
        if ($tenant->companies()->count() <= 1) {
            return false;
        }

        return !$company->documents()->exists();
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id
            && $user->can('manage_companies');
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }

    public function manageCertificates(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id
            && $user->can('manage_certificates');
    }

    public function manageEmissionPoints(User $user, Company $company): bool
    {
        return $user->tenant_id === $company->tenant_id
            && $user->can('manage_companies');
    }
}
