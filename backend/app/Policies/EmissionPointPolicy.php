<?php

namespace App\Policies;

use App\Models\Tenant\EmissionPoint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmissionPointPolicy
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

    public function view(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->tenant_id === $emissionPoint->company->tenant_id;
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
        $currentCount = $tenant->companies()
            ->withCount('emissionPoints')
            ->get()
            ->sum('emission_points_count');

        return $currentCount < $tenant->max_emission_points;
    }

    public function update(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->tenant_id === $emissionPoint->company->tenant_id
            && $user->can('manage_companies');
    }

    public function delete(User $user, EmissionPoint $emissionPoint): bool
    {
        if ($user->tenant_id !== $emissionPoint->company->tenant_id) {
            return false;
        }

        if (!$user->can('manage_companies')) {
            return false;
        }

        return !$emissionPoint->documents()->exists();
    }

    public function restore(User $user, EmissionPoint $emissionPoint): bool
    {
        return $user->tenant_id === $emissionPoint->company->tenant_id
            && $user->can('manage_companies');
    }

    public function forceDelete(User $user, EmissionPoint $emissionPoint): bool
    {
        return false;
    }
}
