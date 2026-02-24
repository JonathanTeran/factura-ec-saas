<?php
$kernel = app(Illuminate\Contracts\Http\Kernel::class);

$now = now();

$planId = \Illuminate\Support\Facades\DB::table('plans')->value('id');
if (!$planId) {
    $planId = \Illuminate\Support\Facades\DB::table('plans')->insertGetId([
        'name' => 'Smoke Plan',
        'slug' => 'smoke-plan',
        'description' => 'Smoke test',
        'price_monthly' => 0,
        'price_yearly' => 0,
        'currency' => 'USD',
        'max_documents_per_month' => 1000,
        'max_users' => 10,
        'max_companies' => 10,
        'max_emission_points' => 10,
        'has_electronic_signature' => 1,
        'has_api_access' => 1,
        'has_inventory' => 1,
        'has_pos' => 1,
        'has_recurring_invoices' => 1,
        'has_proformas' => 1,
        'has_ats' => 1,
        'has_thermal_printer' => 1,
        'has_advanced_reports' => 1,
        'has_whitelabel_ride' => 1,
        'has_webhooks' => 1,
        'has_client_portal' => 1,
        'has_multi_currency' => 1,
        'has_accountant_access' => 1,
        'has_ai_categorization' => 0,
        'support_level' => 'community',
        'support_response_hours' => 72,
        'is_active' => 1,
        'is_featured' => 0,
        'sort_order' => 0,
        'trial_days' => 14,
        'features_json' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$tenantId = \Illuminate\Support\Facades\DB::table('tenants')->value('id');
if (!$tenantId) {
    $tenantId = \Illuminate\Support\Facades\DB::table('tenants')->insertGetId([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'name' => 'Smoke Tenant',
        'slug' => 'smoke-tenant',
        'owner_email' => 'smoke@example.com',
        'status' => 'active',
        'trial_ends_at' => $now->copy()->addDays(10),
        'current_plan_id' => $planId,
        'subscription_status' => 'active',
        'max_documents_per_month' => 1000,
        'max_users' => 10,
        'max_companies' => 10,
        'max_emission_points' => 10,
        'has_api_access' => 1,
        'has_inventory' => 1,
        'has_pos' => 1,
        'has_recurring_invoices' => 1,
        'has_advanced_reports' => 1,
        'has_whitelabel_ride' => 1,
        'documents_this_month' => 0,
        'documents_month_reset_at' => $now->copy()->startOfMonth(),
        'referral_code' => strtoupper(Illuminate\Support\Str::random(8)),
        'referred_by_tenant_id' => null,
        'settings' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$superAdminId = \Illuminate\Support\Facades\DB::table('users')->where('role', 'super_admin')->value('id');
if (!$superAdminId) {
    $superAdminId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
        'tenant_id' => null,
        'name' => 'Smoke Super Admin',
        'email' => 'smoke-superadmin@example.com',
        'email_verified_at' => $now,
        'password' => Illuminate\Support\Facades\Hash::make('password'),
        'phone' => null,
        'avatar_path' => null,
        'role' => 'super_admin',
        'is_active' => 1,
        'timezone' => 'America/Guayaquil',
        'locale' => 'es',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
        'last_login_at' => null,
        'last_login_ip' => null,
        'remember_token' => Illuminate\Support\Str::random(10),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$normalUserId = \Illuminate\Support\Facades\DB::table('users')->where('tenant_id', $tenantId)->value('id');
if (!$normalUserId) {
    $normalUserId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
        'tenant_id' => $tenantId,
        'name' => 'Smoke Tenant User',
        'email' => 'smoke-tenant-user@example.com',
        'email_verified_at' => $now,
        'password' => Illuminate\Support\Facades\Hash::make('password'),
        'phone' => null,
        'avatar_path' => null,
        'role' => 'tenant_owner',
        'is_active' => 1,
        'timezone' => 'America/Guayaquil',
        'locale' => 'es',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
        'last_login_at' => null,
        'last_login_ip' => null,
        'remember_token' => Illuminate\Support\Str::random(10),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$subscriptionId = \Illuminate\Support\Facades\DB::table('subscriptions')->value('id');
if (!$subscriptionId) {
    $subscriptionId = \Illuminate\Support\Facades\DB::table('subscriptions')->insertGetId([
        'tenant_id' => $tenantId,
        'plan_id' => $planId,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'trial_ends_at' => null,
        'starts_at' => $now,
        'ends_at' => $now->copy()->addMonth(),
        'cancelled_at' => null,
        'payment_gateway' => 'stripe',
        'gateway_subscription_id' => 'sub_smoke',
        'gateway_customer_id' => 'cus_smoke',
        'amount' => 9.99,
        'currency' => 'USD',
        'discount_percent' => 0,
        'coupon_code' => null,
        'metadata' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$paymentId = \Illuminate\Support\Facades\DB::table('payments')->value('id');
if (!$paymentId) {
    $paymentId = \Illuminate\Support\Facades\DB::table('payments')->insertGetId([
        'tenant_id' => $tenantId,
        'subscription_id' => $subscriptionId,
        'amount' => 9.99,
        'currency' => 'USD',
        'status' => 'completed',
        'payment_method' => 'stripe',
        'gateway_payment_id' => 'pay_smoke',
        'gateway_response' => json_encode([]),
        'invoice_number' => 'PAY-SMOKE-000001',
        'invoice_path' => null,
        'description' => 'Smoke payment',
        'paid_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
        'transfer_receipt_path' => null,
        'transfer_reference' => null,
        'approved_by' => null,
        'approved_at' => null,
        'failed_at' => null,
        'admin_notes' => null,
    ]);
}

$couponId = \Illuminate\Support\Facades\DB::table('coupons')->value('id');
if (!$couponId) {
    $couponId = \Illuminate\Support\Facades\DB::table('coupons')->insertGetId([
        'code' => 'SMOKE10',
        'description' => 'Smoke coupon',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'max_uses' => 100,
        'times_used' => 0,
        'valid_from' => $now->copy()->subDay(),
        'valid_until' => $now->copy()->addMonth(),
        'applicable_plans' => json_encode([$planId]),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$otherTenantId = \Illuminate\Support\Facades\DB::table('tenants')->where('id', '!=', $tenantId)->value('id');
if (!$otherTenantId) {
    $otherTenantId = \Illuminate\Support\Facades\DB::table('tenants')->insertGetId([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'name' => 'Smoke Tenant B',
        'slug' => 'smoke-tenant-b',
        'owner_email' => 'smoke-b@example.com',
        'status' => 'active',
        'trial_ends_at' => $now->copy()->addDays(10),
        'current_plan_id' => $planId,
        'subscription_status' => 'active',
        'max_documents_per_month' => 1000,
        'max_users' => 10,
        'max_companies' => 10,
        'max_emission_points' => 10,
        'has_api_access' => 1,
        'has_inventory' => 1,
        'has_pos' => 1,
        'has_recurring_invoices' => 1,
        'has_advanced_reports' => 1,
        'has_whitelabel_ride' => 1,
        'documents_this_month' => 0,
        'documents_month_reset_at' => $now->copy()->startOfMonth(),
        'referral_code' => strtoupper(Illuminate\Support\Str::random(8)),
        'referred_by_tenant_id' => null,
        'settings' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$commissionId = \Illuminate\Support\Facades\DB::table('referral_commissions')->value('id');
if (!$commissionId) {
    $commissionId = \Illuminate\Support\Facades\DB::table('referral_commissions')->insertGetId([
        'referrer_tenant_id' => $otherTenantId,
        'referred_tenant_id' => $tenantId,
        'payment_id' => $paymentId,
        'commission_amount' => 1.00,
        'commission_percent' => 10.00,
        'status' => 'pending',
        'paid_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$catalogId = \Illuminate\Support\Facades\DB::table('sri_catalogs')->value('id');
if (!$catalogId) {
    $catalogId = \Illuminate\Support\Facades\DB::table('sri_catalogs')->insertGetId([
        'catalog_type' => 'tax_rate',
        'code' => 'SMK',
        'name' => 'Smoke Tax',
        'description' => 'Smoke catalog',
        'percentage' => 12,
        'is_active' => 1,
        'metadata' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$companyId = \Illuminate\Support\Facades\DB::table('companies')->value('id');
if (!$companyId) {
    $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
        'tenant_id' => $tenantId,
        'ruc' => '1790012345001',
        'business_name' => 'Smoke Company SA',
        'trade_name' => 'SmokeCo',
        'legal_representative' => null,
        'taxpayer_type' => 'juridical',
        'obligated_accounting' => 1,
        'special_taxpayer' => 0,
        'special_taxpayer_number' => null,
        'retention_agent_number' => null,
        'rimpe_type' => 'none',
        'address' => 'Av. Smoke 123',
        'city' => 'Quito',
        'province' => 'Pichincha',
        'phone' => '0999999999',
        'email' => 'smoke-company@example.com',
        'logo_path' => null,
        'sri_environment' => '1',
        'signature_path' => null,
        'signature_password' => null,
        'signature_expires_at' => null,
        'signature_issuer' => null,
        'signature_subject' => null,
        'is_active' => 1,
        'activated_at' => $now,
        'settings' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$branchId = \Illuminate\Support\Facades\DB::table('branches')->value('id');
if (!$branchId) {
    $branchId = \Illuminate\Support\Facades\DB::table('branches')->insertGetId([
        'tenant_id' => $tenantId,
        'company_id' => $companyId,
        'code' => '001',
        'name' => 'Matriz',
        'address' => 'Av. Smoke 123',
        'city' => 'Quito',
        'phone' => '0999999999',
        'is_main' => 1,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$emissionPointId = \Illuminate\Support\Facades\DB::table('emission_points')->value('id');
if (!$emissionPointId) {
    $emissionPointId = \Illuminate\Support\Facades\DB::table('emission_points')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'code' => '001',
        'name' => 'Principal',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$customerId = \Illuminate\Support\Facades\DB::table('customers')->value('id');
if (!$customerId) {
    $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
        'tenant_id' => $tenantId,
        'identification_type' => '05',
        'identification' => '0912345678',
        'name' => 'Cliente Smoke',
        'email' => 'cliente-smoke@example.com',
        'phone' => '0991111111',
        'address' => 'Calle Smoke',
        'city' => 'Quito',
        'is_active' => 1,
        'total_invoiced' => 0,
        'last_invoice_date' => null,
        'notes' => null,
        'tags' => json_encode([]),
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$documentId = \Illuminate\Support\Facades\DB::table('electronic_documents')->value('id');
if (!$documentId) {
    $documentId = \Illuminate\Support\Facades\DB::table('electronic_documents')->insertGetId([
        'tenant_id' => $tenantId,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'emission_point_id' => $emissionPointId,
        'customer_id' => $customerId,
        'created_by' => $normalUserId,
        'document_type' => '01',
        'environment' => '1',
        'series' => '001001',
        'sequential' => '000000001',
        'access_key' => null,
        'status' => 'draft',
        'authorization_number' => null,
        'authorization_date' => null,
        'subtotal_no_tax' => 0,
        'subtotal_0' => 10,
        'subtotal_5' => 0,
        'subtotal_12' => 0,
        'subtotal_15' => 0,
        'total_discount' => 0,
        'total_tax' => 0,
        'total_ice' => 0,
        'tip' => 0,
        'total' => 10,
        'xml_unsigned_path' => null,
        'xml_signed_path' => null,
        'xml_authorized_path' => null,
        'ride_pdf_path' => null,
        'sri_response' => null,
        'sri_errors' => null,
        'sri_attempts' => 0,
        'last_sri_attempt_at' => null,
        'related_document_id' => null,
        'related_document_type' => null,
        'related_document_number' => null,
        'related_document_date' => null,
        'email_sent' => 0,
        'email_sent_at' => null,
        'whatsapp_sent' => 0,
        'whatsapp_sent_at' => null,
        'payment_methods' => json_encode([['code' => '01', 'amount' => 10]]),
        'additional_info' => json_encode([]),
        'issue_date' => $now->toDateString(),
        'due_date' => null,
        'currency' => 'DOLAR',
        'notes' => null,
        'recurring_invoice_id' => null,
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => null,
    ]);
}

$recordMap = [
    'coupons' => $couponId,
    'electronic-documents' => $documentId,
    'payments' => $paymentId,
    'plans' => $planId,
    'referral-commissions' => $commissionId,
    's-r-i-catalogs' => $catalogId,
    'subscriptions' => $subscriptionId,
    'tenants' => $tenantId,
    'users' => $superAdminId,
];

$routes = app('router')->getRoutes();
$failures = [];
$checked = 0;

foreach ($routes as $route) {
    $methods = $route->methods();
    if (!in_array('GET', $methods, true) && !in_array('HEAD', $methods, true)) {
        continue;
    }

    $uri = $route->uri();
    if (!str_starts_with($uri, 'admin')) {
        continue;
    }

    $path = $uri;
    if (str_contains($path, '{record}')) {
        $parts = explode('/', $path);
        $resource = $parts[1] ?? null;
        $recordId = $resource ? ($recordMap[$resource] ?? null) : null;
        if (!$recordId) {
            continue;
        }
        $path = str_replace('{record}', (string) $recordId, $path);
    }

    if (str_contains($path, '{')) {
        continue;
    }

    \Illuminate\Support\Facades\Auth::logout();
    \Illuminate\Support\Facades\Auth::loginUsingId($superAdminId);

    $request = Illuminate\Http\Request::create('/' . ltrim($path, '/'), 'GET');

    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $checked++;

        if ($status >= 500) {
            $failures[] = ['path' => '/' . ltrim($path, '/'), 'status' => $status];
        }
    } catch (Throwable $e) {
        $checked++;
        $failures[] = ['path' => '/' . ltrim($path, '/'), 'status' => 'exception', 'error' => $e->getMessage()];
    }
}

echo "checked={$checked}\n";
if (empty($failures)) {
    echo "failures=0\n";
    exit(0);
}

echo "failures=" . count($failures) . "\n";
foreach ($failures as $failure) {
    echo json_encode($failure, JSON_UNESCAPED_UNICODE) . "\n";
}
exit(1);
