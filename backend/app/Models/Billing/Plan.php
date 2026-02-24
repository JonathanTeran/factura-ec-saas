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

        if ($this->max_companies === -1) {
            $features[] = 'Empresas ilimitadas';
        } elseif ($this->max_companies > 1) {
            $features[] = $this->max_companies . ' empresas';
        }

        if ($this->max_emission_points === -1) {
            $features[] = 'Puntos de emision ilimitados';
        } elseif ($this->max_emission_points > 1) {
            $features[] = $this->max_emission_points . ' puntos de emision';
        }

        // Boolean features
        if ($this->has_electronic_signature) $features[] = 'Firma electronica';
        if ($this->has_proformas) $features[] = 'Proformas';
        if ($this->has_ats) $features[] = 'ATS';
        if ($this->has_accountant_access) $features[] = 'Acceso para contador';
        if ($this->has_api_access) $features[] = 'API REST';
        if ($this->has_inventory) $features[] = 'Inventario';
        if ($this->has_pos) $features[] = 'Punto de venta';
        if ($this->has_recurring_invoices) $features[] = 'Facturacion recurrente';
        if ($this->has_advanced_reports) $features[] = 'Reportes avanzados';
        if ($this->has_thermal_printer) $features[] = 'Impresora termica';
        if ($this->has_webhooks) $features[] = 'Webhooks';
        if ($this->has_client_portal) $features[] = 'Portal de clientes';
        if ($this->has_multi_currency) $features[] = 'Multi-moneda';
        if ($this->has_whitelabel_ride) $features[] = 'RIDE personalizado';
        if ($this->has_ai_categorization) $features[] = 'Categorizacion con IA';

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
