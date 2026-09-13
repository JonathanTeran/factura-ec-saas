<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Billing\Plan;
use App\Services\Landing\PublicLandingCache;
use App\Services\Settings\PricingContentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Datos públicos que consume la landing (Next.js): planes activos con precio
 * y textos editoriales de la sección de precios. Sin autenticación.
 */
class PublicLandingController extends ApiController
{
    public function show(): JsonResponse
    {
        $data = Cache::remember(PublicLandingCache::KEY, PublicLandingCache::TTL_SECONDS, fn () => [
            'plans' => $this->plans(),
            'pricing_content' => $this->pricingContent(),
        ]);

        return $this->success($data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return Plan::active()
            ->where(fn ($q) => $q->where('price_monthly', '>', 0)->orWhere('is_contact_sales', true))
            ->ordered()
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                // Planes a medida: el precio no se publica; el interesado nos contacta.
                'price_monthly' => $plan->is_contact_sales ? 0.0 : (float) $plan->price_monthly,
                'price_yearly' => $plan->is_contact_sales ? 0.0 : (float) $plan->price_yearly,
                'currency' => $plan->currency ?: 'USD',
                'is_featured' => (bool) $plan->is_featured,
                'is_contact_sales' => (bool) $plan->is_contact_sales,
                'yearly_savings_percent' => $plan->is_contact_sales ? 0 : (int) $plan->getYearlySavingsPercent(),
                'features_list' => $plan->getFeaturesList(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingContent(): array
    {
        try {
            return app(PricingContentSettings::class)->all();
        } catch (\Throwable) {
            return array_map(fn (array $d) => $d['default'], PricingContentSettings::definitions());
        }
    }
}
