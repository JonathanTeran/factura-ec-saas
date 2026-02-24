<?php

namespace App\Actions\Fortify;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'terms' => ['required', 'accepted'],
        ], [
            'name.required' => 'El nombre es requerido.',
            'company_name.required' => 'El nombre de la empresa es requerido.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'terms.accepted' => 'Debes aceptar los términos y condiciones.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Get plan: from registration form (query string) or first active plan
            $defaultPlan = null;
            if (!empty($input['plan'])) {
                $defaultPlan = Plan::where('slug', $input['plan'])->where('is_active', true)->first();
            }
            $defaultPlan = $defaultPlan ?? Plan::where('is_active', true)->orderBy('sort_order')->first();

            // Referral tracking: find referring tenant by referral_code
            $referredByTenantId = null;
            if (!empty($input['ref'])) {
                $referrer = Tenant::where('referral_code', $input['ref'])->first();
                if ($referrer) {
                    $referredByTenantId = $referrer->id;
                }
            }

            // Create tenant
            $tenant = Tenant::create([
                'name' => $input['company_name'],
                'slug' => Str::slug($input['company_name']) . '-' . Str::random(4),
                'owner_email' => $input['email'],
                'status' => TenantStatus::ACTIVE,
                'trial_ends_at' => null,
                'current_plan_id' => $defaultPlan?->id,
                'referred_by_tenant_id' => $referredByTenantId,
                'max_documents_per_month' => $defaultPlan?->max_documents_per_month ?? 10,
                'max_users' => $defaultPlan?->max_users ?? 1,
                'max_companies' => $defaultPlan?->max_companies ?? 1,
                'max_emission_points' => $defaultPlan?->max_emission_points ?? 1,
                'has_api_access' => $defaultPlan?->has_api_access ?? false,
                'has_inventory' => $defaultPlan?->has_inventory ?? false,
                'has_pos' => $defaultPlan?->has_pos ?? false,
                'has_recurring_invoices' => $defaultPlan?->has_recurring_invoices ?? false,
                'has_advanced_reports' => $defaultPlan?->has_advanced_reports ?? false,
                'has_whitelabel_ride' => $defaultPlan?->has_whitelabel_ride ?? false,
                'documents_this_month' => 0,
                'documents_month_reset_at' => now()->startOfMonth(),
            ]);

            // Create user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'role' => UserRole::TENANT_OWNER,
                'is_active' => true,
                'email_verified_at' => null, // Will need verification
            ]);

            // Update tenant with owner
            $tenant->update(['owner_id' => $user->id]);

            return $user;
        });
    }
}
