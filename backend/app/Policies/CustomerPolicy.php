<?php

namespace App\Policies;

use App\Models\Tenant\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
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

    public function view(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null && $user->can('manage_customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->can('manage_customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->can('manage_customers')
            && !$customer->documents()->exists();
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id
            && $user->can('manage_customers');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return false;
    }
}
