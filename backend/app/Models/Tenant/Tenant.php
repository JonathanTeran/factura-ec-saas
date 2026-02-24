<?php

namespace App\Models\Tenant;

use App\Enums\TenantStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'owner_email',
        'status',
        'trial_ends_at',
        'current_plan_id',
        'subscription_status',
        'max_documents_per_month',
        'max_users',
        'max_companies',
        'max_emission_points',
        'has_api_access',
        'has_inventory',
        'has_pos',
        'has_recurring_invoices',
        'has_advanced_reports',
        'has_whitelabel_ride',
        'has_webhooks',
        'has_ai_categorization',
        'has_accounting',
        'documents_this_month',
        'documents_month_reset_at',
        'referral_code',
        'referred_by_tenant_id',
        'settings',
        'owner_id',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'subscription_status' => SubscriptionStatus::class,
        'trial_ends_at' => 'datetime',
        'documents_month_reset_at' => 'date',
        'has_api_access' => 'boolean',
        'has_inventory' => 'boolean',
        'has_pos' => 'boolean',
        'has_recurring_invoices' => 'boolean',
        'has_advanced_reports' => 'boolean',
        'has_whitelabel_ride' => 'boolean',
        'has_webhooks' => 'boolean',
        'has_ai_categorization' => 'boolean',
        'has_accounting' => 'boolean',
        'settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (empty($tenant->referral_code)) {
                $tenant->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    // ==================== RELACIONES ====================

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING])
            ->latest('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_by_tenant_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Tenant::class, 'referred_by_tenant_id');
    }

    // ==================== HELPERS ====================

    public function canEmitDocuments(): bool
    {
        return $this->status->canEmitDocuments();
    }

    public function hasReachedDocumentLimit(): bool
    {
        if ($this->max_documents_per_month === -1) {
            return false;
        }

        return $this->documents_this_month >= $this->max_documents_per_month;
    }

    public function remainingDocuments(): int
    {
        if ($this->max_documents_per_month === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_documents_per_month - $this->documents_this_month);
    }

    public function isInTrial(): bool
    {
        return $this->status === TenantStatus::TRIAL &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function daysLeftInTrial(): int
    {
        if (!$this->isInTrial()) {
            return 0;
        }

        return now()->diffInDays($this->trial_ends_at);
    }

    public function hasFeature(string $feature): bool
    {
        return match ($feature) {
            'api_access' => $this->has_api_access,
            'inventory' => $this->has_inventory,
            'pos' => $this->has_pos,
            'recurring_invoices' => $this->has_recurring_invoices,
            'advanced_reports' => $this->has_advanced_reports,
            'whitelabel_ride' => $this->has_whitelabel_ride,
            'webhooks' => $this->has_webhooks,
            'ai_categorization' => $this->has_ai_categorization,
            'accounting' => $this->has_accounting,
            default => false,
        };
    }

    public function resetMonthlyCounters(): void
    {
        $this->update([
            'documents_this_month' => 0,
            'documents_month_reset_at' => now()->startOfMonth(),
        ]);
    }

    public function syncPlanLimits(Plan $plan): void
    {
        $this->update([
            'current_plan_id' => $plan->id,
            'max_documents_per_month' => $plan->max_documents_per_month,
            'max_users' => $plan->max_users,
            'max_companies' => $plan->max_companies,
            'max_emission_points' => $plan->max_emission_points,
            'has_api_access' => $plan->has_api_access,
            'has_inventory' => $plan->has_inventory,
            'has_pos' => $plan->has_pos,
            'has_recurring_invoices' => $plan->has_recurring_invoices,
            'has_advanced_reports' => $plan->has_advanced_reports,
            'has_whitelabel_ride' => $plan->has_whitelabel_ride,
            'has_webhooks' => $plan->has_webhooks,
            'has_ai_categorization' => $plan->has_ai_categorization,
        ]);
    }

    // ==================== ADDITIONAL HELPERS ====================

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING])
            ->latest('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\SRI\ElectronicDocument::class);
    }

    public function documentsThisMonth(): HasMany
    {
        return $this->documents()->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function isAccessible(): bool
    {
        // Check if tenant can access the platform
        if (!$this->status) {
            return false;
        }

        return in_array($this->status, [
            TenantStatus::TRIAL,
            TenantStatus::ACTIVE,
        ]);
    }

    public function isOnTrial(): bool
    {
        return $this->isInTrial();
    }

    public function canIssueDocuments(): bool
    {
        // Must be accessible
        if (!$this->isAccessible()) {
            return false;
        }

        // Check document limits
        return !$this->hasReachedDocumentLimit();
    }

    public function getDocumentsIssuedThisMonthAttribute(): int
    {
        return $this->documents_this_month ?? 0;
    }

    public function incrementDocumentCount(): void
    {
        // Reset counter if new month
        $resetDate = $this->documents_month_reset_at;
        if (!$resetDate || $resetDate->month !== now()->month) {
            $this->resetMonthlyCounters();
        }

        $this->increment('documents_this_month');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getTrialDaysRemainingAttribute(): int
    {
        return $this->daysLeftInTrial();
    }
}
