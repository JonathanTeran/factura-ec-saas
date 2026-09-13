<?php

namespace App\Http\Controllers\Api\V1\Ext;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/ext/me — identidad de la integración: cuenta, plan, límites,
 * uso del período y datos de la llave con la que se autentica.
 */
class MeController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('api_tenant');
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $tenant->loadMissing(['currentPlan', 'activeSubscription']);
        $plan = $tenant->currentPlan;
        $subscription = $tenant->activeSubscription;

        return $this->success([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'status' => $tenant->status?->value ?? $tenant->status,
            ],
            'plan' => $plan ? [
                'slug' => $plan->slug,
                'name' => $plan->name,
            ] : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status?->value ?? $subscription->status,
                'billing_cycle' => $subscription->billing_cycle?->value ?? $subscription->billing_cycle,
                'ends_at' => $subscription->ends_at?->toISOString(),
            ] : null,
            'limits' => [
                'documents_per_month' => (int) $tenant->max_documents_per_month,
                'effective_document_limit' => (int) $tenant->effectiveDocumentLimit(),
                'unlimited' => (int) $tenant->max_documents_per_month === -1,
                'rate_limit_per_minute' => $apiKey->effectiveRateLimit(),
            ],
            'usage' => [
                'documents_this_period' => (int) $tenant->documentsUsedThisPeriod(),
                'period_start' => $tenant->currentDocumentPeriodStart()?->toDateString(),
            ],
            'api_key' => [
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'scopes' => $apiKey->scopes(),
                'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                'effective_rate_limit' => $apiKey->effectiveRateLimit(),
                'expires_at' => $apiKey->expires_at?->toISOString(),
            ],
        ]);
    }
}
