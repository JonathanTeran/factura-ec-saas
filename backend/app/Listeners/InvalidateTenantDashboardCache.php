<?php

namespace App\Listeners;

use App\Events\DocumentAuthorized;
use App\Events\DocumentRejected;
use App\Services\Cache\TenantCacheService;

class InvalidateTenantDashboardCache
{
    public function handle(DocumentAuthorized|DocumentRejected $event): void
    {
        $tenantId = $event->document->tenant_id;

        if ($tenantId) {
            TenantCacheService::invalidateDashboard($tenantId);
        }
    }
}
