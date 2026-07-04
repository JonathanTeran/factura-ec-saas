<?php

namespace App\Models\Billing;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_documents_per_month',
        'max_users',
        'max_companies',
        'max_emission_points',
        'has_electronic_signature',
        'has_api_access',
        'has_inventory',
        'has_pos',
        'has_recurring_invoices',
        'has_proformas',
        'has_ats',
        'has_thermal_printer',
        'has_advanced_reports',
        'has_whitelabel_ride',
        'has_webhooks',
        'has_client_portal',
        'has_multi_currency',
        'has_accountant_access',
        'has_ai_categorization',
        'has_priority_queue',
        'has_bulk_operations',
        'has_custom_roles',
        'has_sso',
        'has_dedicated_manager',
        'has_custom_integrations',
        'has_sla',
        'support_level',
        'support_response_hours',
        'is_active',
        'is_featured',
        'sort_order',
        'trial_days',
        'features_json',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'has_electronic_signature' => 'boolean',
        'has_api_access' => 'boolean',
        'has_inventory' => 'boolean',
        'has_pos' => 'boolean',
        'has_recurring_invoices' => 'boolean',
        'has_proformas' => 'boolean',
        'has_ats' => 'boolean',
        'has_thermal_printer' => 'boolean',
        'has_advanced_reports' => 'boolean',
        'has_whitelabel_ride' => 'boolean',
        'has_webhooks' => 'boolean',
        'has_client_portal' => 'boolean',
        'has_multi_currency' => 'boolean',
        'has_accountant_access' => 'boolean',
        'has_ai_categorization' => 'boolean',
        'has_priority_queue' => 'boolean',
        'has_bulk_operations' => 'boolean',
        'has_custom_roles' => 'boolean',
        'has_sso' => 'boolean',
        'has_dedicated_manager' => 'boolean',
        'has_custom_integrations' => 'boolean',
        'has_sla' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'features_json' => 'array',
    ];

    // ==================== RELACIONES ====================

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'current_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ==================== HELPERS ====================

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function hasUnlimitedDocuments(): bool
    {
        return $this->max_documents_per_month === -1;
    }

    public function getYearlySavings(): float
    {
        $yearlyIfMonthly = $this->price_monthly * 12;
        return $yearlyIfMonthly - $this->price_yearly;
    }

    public function getYearlySavingsPercent(): float
    {
        if ($this->price_monthly == 0) {
            return 0;
        }

        $yearlyIfMonthly = $this->price_monthly * 12;
        return round((($yearlyIfMonthly - $this->price_yearly) / $yearlyIfMonthly) * 100);
    }

    public function getFeaturesList(): array
    {
        // Si el super admin definió una lista personalizada de características
        // para este plan, esa lista manda y se muestra tal cual en la web.
        if (is_array($this->features_json)) {
            $custom = array_values(array_filter(array_map(
                fn ($f) => is_string($f) ? trim($f) : '',
                $this->features_json
            )));

            if (count($custom) > 0) {
                return $custom;
            }
        }

        $features = [];

        // Limits
        if ($this->max_documents_per_month === -1) {
            $features[] = 'Documentos ilimitados';
        } else {
            $features[] = $this->max_documents_per_month . ' documentos/mes';
        }

        if ($this->max_users === -1) {
            $features[] = 'Usuarios ilimitados';
        } else {
            $features[] = $this->max_users . ' usuario' . ($this->max_users > 1 ? 's' : '');
        }

        // Multi-empresa: valor agregado visible en todos los planes.
        if ($this->max_companies === -1) {
            $features[] = 'Empresas (RUC) ilimitadas';
        } elseif ($this->max_companies > 1) {
            $features[] = 'Hasta ' . $this->max_companies . ' empresas (RUCs)';
        } else {
            $features[] = '1 empresa (RUC)';
        }

        if ($this->max_emission_points === -1) {
            $features[] = 'Puntos de emision ilimitados';
        } elseif ($this->max_emission_points > 1) {
            $features[] = $this->max_emission_points . ' puntos de emision';
        }

        // Boolean features
        // La firma electronica NO se anuncia como incluida: el cliente usa su
        // propio certificado (no lo vendemos). Firmamos por el con su .p12.
        if ($this->has_proformas) $features[] = 'Proformas';
        if ($this->has_ats) $features[] = 'ATS';
        if ($this->has_accountant_access) $features[] = 'Acceso para contador';
        if ($this->has_api_access) $features[] = 'API REST';
        if ($this->has_inventory) $features[] = 'Inventario';
        if ($this->has_pos) $features[] = 'Punto de venta';
        if ($this->has_recurring_invoices) $features[] = 'Facturacion recurrente';
        if ($this->has_advanced_reports) $features[] = 'Reportes avanzados';
        if ($this->has_thermal_printer) $features[] = 'Impresora termica';
        // Webhooks aún no está implementado en el backend: no se anuncia.
        if ($this->has_client_portal) $features[] = 'Portal de clientes';
        if ($this->has_multi_currency) $features[] = 'Multi-moneda';
        if ($this->has_whitelabel_ride) $features[] = 'RIDE personalizado';
        if ($this->has_ai_categorization) $features[] = 'Categorizacion con IA';
        if ($this->has_priority_queue) $features[] = 'Emision prioritaria al SRI';
        if ($this->has_bulk_operations) $features[] = 'Emision y carga masiva';
        if ($this->has_custom_roles) $features[] = 'Roles y permisos personalizados';
        if ($this->has_sso) $features[] = 'Inicio de sesion SSO/SAML';
        if ($this->has_dedicated_manager) $features[] = 'Ejecutivo de cuenta dedicado';
        if ($this->has_custom_integrations) $features[] = 'Integraciones a medida';
        if ($this->has_sla) $features[] = 'SLA de disponibilidad garantizado';

        // Support
        $supportLabels = [
            'community' => 'Soporte comunitario',
            'email' => 'Soporte por email (' . $this->support_response_hours . 'h)',
            'priority' => 'Soporte prioritario (' . $this->support_response_hours . 'h)',
            'dedicated' => 'Soporte dedicado (' . $this->support_response_hours . 'h)',
        ];
        $features[] = $supportLabels[$this->support_level] ?? 'Soporte';

        return $features;
    }
}
