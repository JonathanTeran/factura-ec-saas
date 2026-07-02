<?php

namespace App\Services\Cache;

use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Manages tenant-scoped Redis cache tags.
 *
 * TTLs:
 *  - dashboard stats / chart     : 5 min  (invalidated on doc create/authorize)
 *  - recent documents            : 2 min
 *  - monthly summary             : 15 min
 *  - plans list                  : 1 hour
 *  - active subscription         : 10 min (invalidated on subscribe/cancel)
 *  - tenant model (+ subscription): 10 min (invalidated on any state change)
 */
class TenantCacheService
{
    public static function invalidateDashboard(int $tenantId): void
    {
        Cache::tags(["tenant:{$tenantId}", 'dashboard'])->flush();
    }

    public static function invalidateSubscription(int $tenantId): void
    {
        Cache::tags(["tenant:{$tenantId}", 'subscription'])->flush();
        Cache::forget("tenant:{$tenantId}:model");
    }

    public static function invalidatePlans(): void
    {
        Cache::forget('billing:plans');
    }

    public static function invalidateTenant(int $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:model");
        Cache::tags(["tenant:{$tenantId}", 'subscription'])->flush();
    }

    /**
     * Returns the tenant with activeSubscription + plan pre-loaded, cached 10 min.
     * Avoids 2 extra queries (subscriptions + plans) on every authenticated request.
     */
    public static function tenantWithSubscription(int $tenantId): ?Tenant
    {
        return Cache::remember(
            "tenant:{$tenantId}:model",
            now()->addMinutes(10),
            fn () => Tenant::with(['activeSubscription.plan', 'currentPlan'])
                ->find($tenantId),
        );
    }
}

