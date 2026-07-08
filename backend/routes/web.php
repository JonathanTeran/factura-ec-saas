<?php

// ─────────────────────────────────────────────────────────────────
//  Web routes
//
//  El panel principal vive en Next.js (frontend/). Esta capa Laravel:
//    1. Redirige rutas /panel/* migradas hacia FRONTEND_URL (Next.js)
//    2. Conserva en Livewire SOLO los módulos no migrados todavía:
//         · Onboarding wizard
//         · Settings: webhooks, api-keys, activity-log, referrals
//         · Accounting: setup wizard, settings, mappings
//
//  Cuando FRONTEND_URL no está configurado, todas las rutas vuelven a
//  Livewire (rollback inmediato sin redeploy).
// ─────────────────────────────────────────────────────────────────

use App\Livewire\Panel\Onboarding\OnboardingWizard;
use App\Livewire\Panel\Settings\SettingsIndex;
use App\Livewire\Panel\Settings\WebhookSettings;
use App\Livewire\Panel\Settings\ReferralDashboard;
use App\Livewire\Panel\Settings\ApiKeySettings;
use App\Livewire\Panel\Settings\ActivityLogSettings;
use App\Livewire\Panel\Customers\CustomerImport;
use App\Livewire\Panel\Products\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Legal pages
Route::get('/terms', fn () => view('pages.terms'))->name('terms');
Route::get('/privacy', fn () => view('pages.privacy'))->name('privacy');

// Descargas del super admin (Filament → ElectronicDocumentResource): XML
// autorizado y RIDE de cualquier tenant. Solo super admin; el recurso ya
// referencia estos nombres de ruta (la página 500-eaba si no existían).
Route::middleware('auth')->prefix('admin/documents')->name('admin.documents.')->group(function () {
    $download = function (Request $request, int $document, string $field, string $ext) {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $doc = \App\Models\SRI\ElectronicDocument::withoutGlobalScopes()->findOrFail($document);
        $path = $doc->{$field};
        abort_unless($path && \Illuminate\Support\Facades\Storage::exists($path), 404);

        $name = str_replace('-', '_', $doc->getDocumentNumber()).'.'.$ext;

        return \Illuminate\Support\Facades\Storage::download($path, $name);
    };

    Route::get('{document}/xml', fn (Request $request, int $document) => $download($request, $document, 'xml_authorized_path', 'xml'))
        ->whereNumber('document')
        ->name('download-xml');

    Route::get('{document}/ride', fn (Request $request, int $document) => $download($request, $document, 'ride_pdf_path', 'pdf'))
        ->whereNumber('document')
        ->name('download-ride');
});

// Impersonación desde Filament (TenantResource → botón "Impersonar"): el super
// admin entra al panel como el dueño del tenant. Para volver al admin hay que
// re-loguearse en /admin (la sesión cambia de usuario).
Route::get('admin/tenants/{tenant}/impersonate', function (Request $request, \App\Models\Tenant\Tenant $tenant) {
    abort_unless($request->user()?->isSuperAdmin(), 403);

    $target = $tenant->owner ?? $tenant->users()->orderBy('id')->first();
    abort_unless($target !== null, 404);

    \Illuminate\Support\Facades\Auth::login($target);
    $request->session()->regenerate();

    return redirect('/dashboard');
})->middleware('auth')->name('tenant.impersonate');

// Landing page
Route::get('/', function () {
    try {
        $plans = \App\Models\Billing\Plan::active()
            ->where('price_monthly', '>', 0)
            ->ordered()
            ->get();
    } catch (\Throwable) {
        $plans = collect();
    }

    // Textos editoriales de precios, administrables desde el super admin.
    try {
        $pricingContent = app(\App\Services\Settings\PricingContentSettings::class)->all();
    } catch (\Throwable) {
        $pricingContent = array_map(
            fn ($d) => $d['default'],
            \App\Services\Settings\PricingContentSettings::definitions(),
        );
    }

    return view('welcome', compact('plans', 'pricingContent'));
});

// ─── Helpers ────────────────────────────────────────────────────

$frontendUrl = static function (): ?string {
    $url = env('FRONTEND_URL');
    return $url ? rtrim($url, '/') : null;
};

$redirectTo = static function (string $path) use ($frontendUrl) {
    return function (Request $request) use ($path, $frontendUrl) {
        $base = $frontendUrl();
        if (! $base) {
            abort(503, 'Panel migrado a Next.js — define FRONTEND_URL.');
        }
        $qs = $request->getQueryString();
        return redirect($base . $path . ($qs ? '?' . $qs : ''), 301);
    };
};

// ─── Panel ──────────────────────────────────────────────────────

Route::middleware(['auth', 'verified'])->prefix('panel')->name('panel.')->group(function () use ($frontendUrl, $redirectTo) {

    // Onboarding (NO migrado — sigue en Livewire)
    Route::get('onboarding', OnboardingWizard::class)->name('onboarding');

    Route::middleware(\App\Http\Middleware\CheckOnboarding::class)->group(function () use ($frontendUrl, $redirectTo) {

        // ── Bulk import (NO migrado — sigue en Livewire si existen las clases) ──
        if (class_exists(CustomerImport::class)) {
            Route::get('customers/import', CustomerImport::class)->name('customers.import');
        }
        if (class_exists(ProductImport::class)) {
            Route::get('products/import', ProductImport::class)->name('products.import');
        }

        // ── Settings sub-páginas NO migradas ──
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('webhooks', WebhookSettings::class)->name('webhooks');
            Route::get('api-keys', ApiKeySettings::class)->name('api-keys');
            Route::get('activity-log', ActivityLogSettings::class)->name('activity-log');
            Route::get('referrals', ReferralDashboard::class)->name('referrals');
        });

        // ── Accounting sub-páginas NO migradas ──
        Route::prefix('accounting')->name('accounting.')
            ->middleware(\App\Http\Middleware\CheckAccountingAccess::class)
            ->group(function () {
                Route::get('setup', \App\Livewire\Panel\Accounting\AccountingSetupWizard::class)->name('setup');
                Route::get('settings', \App\Livewire\Panel\Accounting\AccountingSettings::class)->name('settings');
                Route::get('settings/mappings', \App\Livewire\Panel\Accounting\AccountMappingSettings::class)->name('settings.mappings');
            });

        // ── Migrados a Next.js — redirect 301 ──
        // Dashboard
        Route::get('/', $redirectTo('/'))->name('dashboard');

        // Documents
        Route::get('documents', $redirectTo('/documents'))->name('documents.index');
        Route::get('documents/create', $redirectTo('/documents/new'))->name('documents.create');
        Route::get('documents/{document}', $redirectTo('/documents/{document}'))
            ->where('document', '[0-9]+')
            ->name('documents.show');
        Route::get('documents/{document}/edit', $redirectTo('/documents/{document}/edit'))
            ->where('document', '[0-9]+')
            ->name('documents.edit');

        // Document type shortcuts
        Route::get('invoices/create', $redirectTo('/documents/new'))->name('invoices.create');
        Route::get('credit-notes/create', $redirectTo('/documents/new?type=04'))->name('credit-notes.create');
        Route::get('debit-notes/create', $redirectTo('/documents/new?type=05'))->name('debit-notes.create');
        Route::get('retention/create', $redirectTo('/documents/new?type=07'))->name('retention.create');
        Route::get('guides/create', $redirectTo('/documents/new?type=06'))->name('guides.create');

        // Customers / Products / Categories (CRUD)
        foreach ([
            'customers' => 'customers',
            'products' => 'products',
            'categories' => 'categories',
            'suppliers' => 'suppliers',
            'quotes' => 'quotes',
            'received-documents' => 'received-documents',
            'personal-expenses' => 'personal-expenses',
            'recurring-invoices' => 'recurring-invoices',
            'purchases' => 'purchases',
            'support' => 'support',
        ] as $segment => $target) {
            Route::get($segment, $redirectTo("/{$target}"))->name(str_replace('-', '_', $segment) . '.index');
            Route::get("{$segment}/create", $redirectTo("/{$target}/new"))->name(str_replace('-', '_', $segment) . '.create');
            Route::get("{$segment}/{id}/edit", $redirectTo("/{$target}/{id}"))
                ->where('id', '[0-9]+')
                ->name(str_replace('-', '_', $segment) . '.edit');
        }

        // Support detail
        Route::get('support/{ticket}', $redirectTo('/support/{ticket}'))
            ->where('ticket', '[0-9]+')
            ->name('support.show');

        // Inventory / POS / Reports / Guides
        Route::get('inventory', $redirectTo('/inventory'))->name('inventory.index');
        Route::get('inventory/movements', $redirectTo('/inventory'))->name('inventory.movements');
        Route::get('pos', $redirectTo('/pos'))->name('pos.index');
        Route::get('pos/history', $redirectTo('/pos/sessions'))->name('pos.history');
        Route::get('reports', $redirectTo('/reports'))->name('reports.index');
        Route::get('guides', $redirectTo('/guides'))->name('guides.index');

        // Settings (índice + páginas migradas)
        Route::get('settings', function (Request $request) use ($frontendUrl) {
            $base = $frontendUrl();
            if (! $base) {
                return app(SettingsIndex::class)($request);
            }
            return redirect($base . '/settings', 301);
        })->name('settings.index');
        Route::get('settings/profile', $redirectTo('/settings/profile'))->name('settings.profile');
        Route::get('settings/company', $redirectTo('/settings/establishments'))->name('settings.company');
        Route::get('settings/billing', $redirectTo('/settings/subscription'))->name('settings.billing');

        // Accounting (módulos migrados)
        Route::prefix('accounting')->name('accounting.')
            ->middleware(\App\Http\Middleware\CheckAccountingAccess::class)
            ->group(function () use ($redirectTo) {
                Route::get('/', $redirectTo('/accounting/accounts'))->name('dashboard');

                Route::get('accounts', $redirectTo('/accounting/accounts'))->name('accounts.index');
                Route::get('accounts/create', $redirectTo('/accounting/accounts/new'))->name('accounts.create');
                Route::get('accounts/{account}/edit', $redirectTo('/accounting/accounts/{account}'))
                    ->where('account', '[0-9]+')
                    ->name('accounts.edit');

                Route::get('journal', $redirectTo('/accounting/journal-entries'))->name('journal.index');
                Route::get('journal/create', $redirectTo('/accounting/journal-entries/new'))->name('journal.create');
                Route::get('journal/{entry}', $redirectTo('/accounting/journal-entries/{entry}'))
                    ->where('entry', '[0-9]+')
                    ->name('journal.show');
                Route::get('journal/{entry}/edit', $redirectTo('/accounting/journal-entries/{entry}'))
                    ->where('entry', '[0-9]+')
                    ->name('journal.edit');

                Route::get('ledger', $redirectTo('/accounting/reports'))->name('ledger');
                Route::get('trial-balance', $redirectTo('/accounting/reports'))->name('trial-balance');
                Route::get('financial-statements', $redirectTo('/accounting/reports'))->name('financial-statements');

                Route::get('tax-forms', $redirectTo('/accounting/tax-forms'))->name('tax-forms.index');
                Route::get('tax-forms/generate/{type}', $redirectTo('/accounting/tax-forms'))->name('tax-forms.generate');
                Route::get('tax-forms/ats', $redirectTo('/accounting/tax-forms'))->name('tax-forms.ats');

                Route::get('cost-centers', $redirectTo('/accounting/cost-centers'))->name('cost-centers.index');

                Route::get('budgets', $redirectTo('/accounting/budgets'))->name('budgets.index');
                Route::get('budgets/create', $redirectTo('/accounting/budgets'))->name('budgets.create');
                Route::get('budgets/{budget}/edit', $redirectTo('/accounting/budgets'))->name('budgets.edit');
                Route::get('budgets/{budget}/execution', $redirectTo('/accounting/budgets'))->name('budgets.execution');

                Route::get('periods', $redirectTo('/accounting/fiscal-periods'))->name('periods');
            });

    }); // end CheckOnboarding middleware group
});

// Logout
Route::post('logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');
