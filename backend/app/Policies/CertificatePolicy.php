<?php

namespace App\Policies;

use App\Models\Tenant\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
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

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->tenant_id === $certificate->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null
            && $user->can('manage_certificates');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->tenant_id === $certificate->tenant_id
            && $user->can('manage_certificates');
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        if ($user->tenant_id !== $certificate->tenant_id) {
            return false;
        }

        if (!$user->can('manage_certificates')) {
            return false;
        }

        return !$certificate->is_active;
    }

    public function restore(User $user, Certificate $certificate): bool
    {
        return $user->tenant_id === $certificate->tenant_id
            && $user->can('manage_certificates');
    }

    public function forceDelete(User $user, Certificate $certificate): bool
    {
        return false;
    }

    public function activate(User $user, Certificate $certificate): bool
    {
        if ($user->tenant_id !== $certificate->tenant_id) {
            return false;
        }

        if (!$user->can('manage_certificates')) {
            return false;
        }

        return !$certificate->isExpired();
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return $user->tenant_id === $certificate->tenant_id
            && $user->can('manage_certificates');
    }
}
