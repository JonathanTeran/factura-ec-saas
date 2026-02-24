<?php

namespace App\Policies;

use App\Models\Tenant\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
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

    public function view(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null && $user->can('manage_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->can('manage_products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->can('manage_products');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->can('manage_products');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    public function manageInventory(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->can('manage_inventory')
            && $user->tenant->has_inventory;
    }
}
