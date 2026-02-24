<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
        return $user->tenant_id !== null && $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->tenant_id === $model->tenant_id
            && $user->can('manage_users');
    }

    public function create(User $user): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        if (!$user->can('manage_users')) {
            return false;
        }

        $tenant = $user->tenant;
        $currentCount = $tenant->users()->count();

        return $currentCount < $tenant->max_users;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        if (!$user->can('manage_users')) {
            return false;
        }

        if ($model->role === UserRole::TENANT_OWNER && $user->role !== UserRole::TENANT_OWNER) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        if (!$user->can('manage_users')) {
            return false;
        }

        if ($model->role === UserRole::TENANT_OWNER) {
            return false;
        }

        return true;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->tenant_id === $model->tenant_id
            && $user->can('manage_users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    public function impersonate(User $user, User $model): bool
    {
        return false;
    }

    public function changeRole(User $user, User $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        if ($user->role !== UserRole::TENANT_OWNER) {
            return false;
        }

        if ($model->role === UserRole::TENANT_OWNER) {
            return false;
        }

        return true;
    }

    public function toggleActive(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        if (!$user->can('manage_users')) {
            return false;
        }

        if ($model->role === UserRole::TENANT_OWNER) {
            return false;
        }

        return true;
    }
}
