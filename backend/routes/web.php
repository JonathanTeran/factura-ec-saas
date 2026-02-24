<?php

use App\Livewire\Panel\Dashboard;
use App\Livewire\Panel\Documents\DocumentList;
use App\Livewire\Panel\Documents\DocumentShow;
use App\Livewire\Panel\Documents\DocumentCreate;
use App\Livewire\Panel\Customers\CustomerList;
use App\Livewire\Panel\Customers\CustomerForm;
use App\Livewire\Panel\Products\ProductList;
use App\Livewire\Panel\Products\ProductForm;
use App\Livewire\Panel\Categories\CategoryList;
use App\Livewire\Panel\Categories\CategoryForm;
use App\Livewire\Panel\Inventory\InventoryDashboard;
use App\Livewire\Panel\Inventory\InventoryMovements;
use App\Livewire\Panel\Reports\ReportsDashboard;
use App\Livewire\Panel\Settings\SettingsIndex;
use App\Livewire\Panel\Settings\ProfileSettings;
use App\Livewire\Panel\Settings\CompanySettings;
use App\Livewire\Panel\Settings\BillingSettings;
use App\Livewire\Panel\Settings\WebhookSettings;
use App\Livewire\Panel\Settings\ReferralDashboard;
use App\Livewire\Panel\RecurringInvoices\RecurringInvoiceList;
use App\Livewire\Panel\RecurringInvoices\RecurringInvoiceForm;
use App\Livewire\Panel\Purchases\PurchaseList;
use App\Livewire\Panel\Purchases\PurchaseForm;
use App\Livewire\Panel\Purchases\SupplierList;
use App\Livewire\Panel\Pos\PosDashboard;
use App\Livewire\Panel\Pos\PosHistory;
use App\Livewire\Panel\Onboarding\OnboardingWizard;
use Illuminate\Support\Facades\Route;

// Legal pages
Route::get('/terms', fn () => view('pages.terms'))->name('terms');
Route::get('/privacy', fn () => view('pages.privacy'))->name('privacy');

Route::get('/', function () {
    try {
        $plans = \App\Models\Billing\Plan::active()
            ->where('price_monthly', '>', 0)
            ->ordered()
            ->get();
    } catch (\Throwable) {
        $plans = collect();
    }

    return view('welcome', compact('plans'));
});

// Auth routes (using Laravel Fortify)
// These will be provided by Fortify

// Panel routes (authenticated users)
Route::middleware(['auth', 'verified'])->prefix('panel')->name('panel.')->group(function () {

    // Onboarding (excluded from CheckOnboarding middleware)
    Route::get('onboarding', OnboardingWizard::class)->name('onboarding');

    // All other panel routes require onboarding to be completed
    Route::middleware(\App\Http\Middleware\CheckOnboarding::class)->group(function () {

    // Dashboard
    Route::get('/', Dashboard::class)->name('dashboard');

    // Documents
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', DocumentList::class)->name('index');
        Route::get('create', DocumentCreate::class)->name('create');
        Route::get('{document}', DocumentShow::class)->name('show');
        Route::get('{document}/edit', DocumentCreate::class)->name('edit');
    });

    // Document type shortcuts (sidebar quick actions)
    Route::get('invoices/create', DocumentCreate::class)->name('invoices.create');
    Route::get('credit-notes/create', DocumentCreate::class)->name('credit-notes.create');
    Route::get('debit-notes/create', DocumentCreate::class)->name('debit-notes.create');
    Route::get('retention/create', DocumentCreate::class)->name('retention.create');
    Route::get('guides/create', DocumentCreate::class)->name('guides.create');

    // Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', CustomerList::class)->name('index');
        Route::get('create', CustomerForm::class)->name('create');
        Route::get('{customer}/edit', CustomerForm::class)->name('edit');
    });

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', ProductList::class)->name('index');
        Route::get('create', ProductForm::class)->name('create');
        Route::get('{product}/edit', ProductForm::class)->name('edit');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', CategoryList::class)->name('index');
        Route::get('create', CategoryForm::class)->name('create');
        Route::get('{category}/edit', CategoryForm::class)->name('edit');
    });

    // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', InventoryDashboard::class)->name('index');
        Route::get('movements', InventoryMovements::class)->name('movements');
    });

    // Recurring Invoices
    Route::prefix('recurring-invoices')->name('recurring-invoices.')->group(function () {
        Route::get('/', RecurringInvoiceList::class)->name('index');
        Route::get('create', RecurringInvoiceForm::class)->name('create');
        Route::get('{recurringInvoice}/edit', RecurringInvoiceForm::class)->name('edit');
    });

    // Purchases (Compras)
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', PurchaseList::class)->name('index');
        Route::get('create', PurchaseForm::class)->name('create');
        Route::get('{purchase}/edit', PurchaseForm::class)->name('edit');
    });

    // Suppliers (Proveedores)
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', SupplierList::class)->name('index');
    });

    // POS (Punto de Venta)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', PosDashboard::class)->name('index');
        Route::get('history', PosHistory::class)->name('history');
    });

    // Reports

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', ReportsDashboard::class)->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', SettingsIndex::class)->name('index');
        Route::get('profile', ProfileSettings::class)->name('profile');
        Route::get('company', CompanySettings::class)->name('company');
        Route::get('billing', BillingSettings::class)->name('billing');
        Route::get('referrals', ReferralDashboard::class)->name('referrals');
        Route::get('webhooks', WebhookSettings::class)->name('webhooks');
    });

    // Accounting (Contabilidad)
    Route::prefix('accounting')->name('accounting.')->middleware(\App\Http\Middleware\CheckAccountingAccess::class)->group(function () {
        Route::get('/', \App\Livewire\Panel\Accounting\AccountingDashboard::class)->name('dashboard');
        Route::get('setup', \App\Livewire\Panel\Accounting\AccountingSetupWizard::class)->name('setup');

        // Plan de cuentas
        Route::get('accounts', \App\Livewire\Panel\Accounting\ChartOfAccountsList::class)->name('accounts.index');
        Route::get('accounts/create', \App\Livewire\Panel\Accounting\ChartOfAccountsForm::class)->name('accounts.create');
        Route::get('accounts/{account}/edit', \App\Livewire\Panel\Accounting\ChartOfAccountsForm::class)->name('accounts.edit');

        // Asientos contables (Libro Diario)
        Route::get('journal', \App\Livewire\Panel\Accounting\JournalEntryList::class)->name('journal.index');
        Route::get('journal/create', \App\Livewire\Panel\Accounting\JournalEntryForm::class)->name('journal.create');
        Route::get('journal/{entry}', \App\Livewire\Panel\Accounting\JournalEntryShow::class)->name('journal.show');
        Route::get('journal/{entry}/edit', \App\Livewire\Panel\Accounting\JournalEntryForm::class)->name('journal.edit');

        // Libro Mayor y Balance
        Route::get('ledger', \App\Livewire\Panel\Accounting\GeneralLedger::class)->name('ledger');
        Route::get('trial-balance', \App\Livewire\Panel\Accounting\TrialBalance::class)->name('trial-balance');

        // Estados Financieros
        Route::get('financial-statements', \App\Livewire\Panel\Accounting\FinancialStatements::class)->name('financial-statements');

        // Formularios SRI
        Route::get('tax-forms', \App\Livewire\Panel\Accounting\TaxForms::class)->name('tax-forms.index');
        Route::get('tax-forms/generate/{type}', \App\Livewire\Panel\Accounting\TaxFormGenerate::class)->name('tax-forms.generate');
        Route::get('tax-forms/ats', \App\Livewire\Panel\Accounting\ATSGenerate::class)->name('tax-forms.ats');

        // Centros de costo
        Route::get('cost-centers', \App\Livewire\Panel\Accounting\CostCenterList::class)->name('cost-centers.index');

        // Presupuestos
        Route::get('budgets', \App\Livewire\Panel\Accounting\BudgetList::class)->name('budgets.index');
        Route::get('budgets/create', \App\Livewire\Panel\Accounting\BudgetForm::class)->name('budgets.create');
        Route::get('budgets/{budget}/edit', \App\Livewire\Panel\Accounting\BudgetForm::class)->name('budgets.edit');
        Route::get('budgets/{budget}/execution', \App\Livewire\Panel\Accounting\BudgetExecution::class)->name('budgets.execution');

        // Períodos fiscales
        Route::get('periods', \App\Livewire\Panel\Accounting\FiscalPeriodManager::class)->name('periods');

        // Configuración
        Route::get('settings', \App\Livewire\Panel\Accounting\AccountingSettings::class)->name('settings');
        Route::get('settings/mappings', \App\Livewire\Panel\Accounting\AccountMappingSettings::class)->name('settings.mappings');
    });

    }); // end CheckOnboarding middleware group
});

// Logout route
Route::post('logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');
