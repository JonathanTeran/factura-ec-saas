<?php

namespace App\Services\Arbitros;

use App\Enums\IdentificationType;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;

/**
 * Activa el vertical de árbitros para un tenant: marca el tipo de negocio y
 * asegura el cliente receptor (FEF) de forma idempotente.
 *
 * Ver docs/arbitros-vertical-spec.md §2 y §3.3.
 */
class RefereeModuleActivator
{
    /**
     * Marca el tenant como árbitro y prepara lo necesario. Idempotente.
     */
    public function activate(Tenant $tenant): void
    {
        if ($tenant->business_type !== Tenant::BUSINESS_TYPE_REFEREE) {
            $tenant->update(['business_type' => Tenant::BUSINESS_TYPE_REFEREE]);
        }

        $this->ensureFefCustomer($tenant);
    }

    /**
     * Crea el cliente FEF si aún no existe. Solo si el RUC está configurado
     * (config/arbitros.php) — no inventamos datos fiscales.
     */
    public function ensureFefCustomer(Tenant $tenant): ?Customer
    {
        $ruc = config('arbitros.fef.ruc');

        if (empty($ruc)) {
            return null;
        }

        $existing = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('identification', $ruc)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Customer::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'identification_type' => IdentificationType::RUC,
            'identification' => $ruc,
            'business_name' => config('arbitros.fef.business_name'),
            'name' => config('arbitros.fef.business_name'),
            'email' => config('arbitros.fef.email'),
            'is_active' => true,
            'notes' => 'Receptor de facturas del árbitro (creado por el módulo de árbitros).',
        ]);
    }
}
