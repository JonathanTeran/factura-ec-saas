<?php

namespace Tests\Traits;

use App\Models\Tenant\ApiKey;

/**
 * Helpers para probar la API de integración. Requiere CreatesTestTenant
 * (usa $this->plan, $this->tenant y $this->user).
 */
trait UsesApiKeys
{
    /** Deja al tenant en un plan con acceso API (slug negocio → 60 req/min). */
    protected function enableApiAccess(string $slug = 'negocio'): void
    {
        $this->plan->update(['has_api_access' => true, 'slug' => $slug]);
        $this->tenant->syncPlanLimits($this->plan->fresh());
        $this->tenant->refresh();
    }

    /** @return array{0: ApiKey, 1: string} llave y su valor en claro */
    protected function makeApiKey(array $overrides = []): array
    {
        $plain = ApiKey::generatePlainKey();

        $key = ApiKey::factory()->withPlainKey($plain)->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'name' => 'Integración de prueba',
            'permissions' => ['*'],
            'rate_limit_per_minute' => 60,
        ], $overrides));

        return [$key, $plain];
    }

    protected function extHeaders(string $plain): array
    {
        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    protected function extGet(string $uri, string $plain, array $headers = [])
    {
        return $this->getJson('/api/v1/ext'.$uri, $this->extHeaders($plain) + $headers);
    }

    protected function extPost(string $uri, string $plain, array $data = [], array $headers = [])
    {
        return $this->postJson('/api/v1/ext'.$uri, $data, $this->extHeaders($plain) + $headers);
    }

    protected function extPatch(string $uri, string $plain, array $data = [])
    {
        return $this->patchJson('/api/v1/ext'.$uri, $data, $this->extHeaders($plain));
    }

    /** Payload mínimo válido de factura (IVA 15 %). */
    protected function invoicePayload(int $customerId, array $overrides = []): array
    {
        return array_replace_recursive([
            'company_id' => $this->company->id,
            'customer_id' => $customerId,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'subtotal_15' => 100.00,
            'total_tax' => 15.00,
            'total' => 115.00,
            'payment_methods' => [['code' => '01', 'amount' => 115.00]],
            'items' => [[
                'main_code' => 'SERV-001',
                'description' => 'Servicio de consultoría',
                'quantity' => 1,
                'unit_price' => 100.00,
                'discount' => 0,
                'subtotal' => 100.00,
                'tax_code' => '2',
                'tax_percentage_code' => '4',
                'tax_rate' => 15,
                'tax_base' => 100.00,
                'tax_value' => 15.00,
            ]],
        ], $overrides);
    }
}
