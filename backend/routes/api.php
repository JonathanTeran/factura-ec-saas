<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\EmissionPointController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\AICategorizationController;
use App\Http\Controllers\Api\V1\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes for mobile app and external integrations
|
*/

// API Version 1
Route::prefix('v1')->group(function () {

    // Public routes (no authentication required)
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:magic-link');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:magic-link');

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', 'tenant.active', 'throttle:api'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::put('password', [ProfileController::class, 'updatePassword']);
        });

        // Companies
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index']);
            Route::get('{company}', [CompanyController::class, 'show']);
            Route::post('{company}/switch', [CompanyController::class, 'switch']);
            Route::get('{company}/branches', [CompanyController::class, 'branches']);
            Route::get('{company}/emission-points', [CompanyController::class, 'emissionPoints']);
        });

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::get('recent-documents', [DashboardController::class, 'recentDocuments']);
            Route::get('monthly-summary', [DashboardController::class, 'monthlySummary']);
            Route::get('chart-data', [DashboardController::class, 'chartData']);
        });

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::prefix('customers')->group(function () {
            Route::get('search/{query}', [CustomerController::class, 'search']);
            Route::get('{customer}/documents', [CustomerController::class, 'documents']);
        });

        // Products
        Route::apiResource('products', ProductController::class);
        Route::prefix('products')->group(function () {
            Route::get('search/{query}', [ProductController::class, 'search']);
            Route::post('{product}/adjust-stock', [ProductController::class, 'adjustStock']);
        });

        // Documents (Electronic Invoices, Credit Notes, etc.)
        Route::apiResource('documents', DocumentController::class);
        Route::prefix('documents')->group(function () {
            Route::post('{document}/send', [DocumentController::class, 'send']);
            Route::post('{document}/void', [DocumentController::class, 'void']);
            Route::get('{document}/ride', [DocumentController::class, 'downloadRide']);
            Route::get('{document}/xml', [DocumentController::class, 'downloadXml']);
            Route::post('{document}/resend-email', [DocumentController::class, 'resendEmail']);
            Route::get('{document}/status', [DocumentController::class, 'checkStatus']);
        });

        // SRI Catalogs
        Route::prefix('catalogs')->group(function () {
            Route::get('identification-types', [CatalogController::class, 'identificationTypes']);
            Route::get('document-types', [CatalogController::class, 'documentTypes']);
            Route::get('payment-methods', [CatalogController::class, 'paymentMethods']);
            Route::get('tax-rates', [CatalogController::class, 'taxRates']);
            Route::get('retention-codes', [CatalogController::class, 'retentionCodes']);
        });

        // Categories
        Route::apiResource('categories', CategoryController::class);
        Route::get('categories-tree', [CategoryController::class, 'tree']);

        // Branches & Emission Points
        Route::prefix('companies/{company}')->group(function () {
            Route::apiResource('branches', BranchController::class);
            Route::get('branches/{branch}/emission-points', [BranchController::class, 'emissionPoints']);
        });

        Route::prefix('branches/{branch}')->group(function () {
            Route::apiResource('emission-points', EmissionPointController::class);
        });

        // Subscriptions & Billing
        Route::prefix('subscription')->group(function () {
            Route::get('plans', [SubscriptionController::class, 'plans']);
            Route::get('current', [SubscriptionController::class, 'current']);
            Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
            Route::post('subscribe-bank-transfer', [SubscriptionController::class, 'subscribeBankTransfer']);
            Route::get('bank-accounts', [SubscriptionController::class, 'bankAccounts']);
            Route::get('payment-status/{id}', [SubscriptionController::class, 'paymentStatus']);
            Route::post('cancel', [SubscriptionController::class, 'cancel']);
            Route::post('resume', [SubscriptionController::class, 'resume']);
            Route::post('change-plan', [SubscriptionController::class, 'changePlan']);
            Route::get('payments', [SubscriptionController::class, 'payments']);
            Route::get('usage', [SubscriptionController::class, 'usage']);
            Route::post('validate-coupon', [SubscriptionController::class, 'validateCoupon']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('dashboard', [ReportController::class, 'dashboard']);
            Route::get('sales', [ReportController::class, 'sales']);
            Route::get('taxes', [ReportController::class, 'taxes']);
            Route::get('top-customers', [ReportController::class, 'topCustomers']);
            Route::get('top-products', [ReportController::class, 'topProducts']);
            Route::get('documents-by-status', [ReportController::class, 'documentsByStatus']);
            Route::get('comparison', [ReportController::class, 'comparison']);
            Route::get('withholdings', [ReportController::class, 'withholdings']);
            Route::get('ats', [ReportController::class, 'ats']);
        });

        // Inventory
        Route::prefix('inventory')->group(function () {
            Route::get('/', [InventoryController::class, 'index']);
            Route::get('low-stock', [InventoryController::class, 'lowStock']);
            Route::get('summary', [InventoryController::class, 'summary']);
            Route::post('products/{product}/adjust', [InventoryController::class, 'adjust']);
            Route::post('products/{product}/purchase', [InventoryController::class, 'purchase']);
            Route::get('products/{product}/movements', [InventoryController::class, 'productMovements']);
        });

        // Suppliers
        Route::apiResource('suppliers', SupplierController::class);
        Route::get('suppliers/search/{query}', [SupplierController::class, 'search']);

        // Purchases (Compras)
        Route::apiResource('purchases', PurchaseController::class);

        // POS (Punto de Venta)
        Route::prefix('pos')->group(function () {
            Route::get('active-session', [PosController::class, 'activeSession']);
            Route::post('open-session', [PosController::class, 'openSession']);
            Route::post('sessions/{session}/close', [PosController::class, 'closeSession']);
            Route::post('sessions/{session}/transactions', [PosController::class, 'createTransaction']);
            Route::get('sessions/{session}/transactions', [PosController::class, 'transactions']);
            Route::post('transactions/{transaction}/void', [PosController::class, 'voidTransaction']);
            Route::get('sessions', [PosController::class, 'sessions']);
        });

        // AI Categorization
        Route::prefix('ai')->group(function () {
            Route::post('categorize/{product}', [AICategorizationController::class, 'categorize']);
            Route::post('categorize-batch', [AICategorizationController::class, 'categorizeBatch']);
            Route::get('suggest-category/{product}', [AICategorizationController::class, 'suggest']);
        });

        // Imports (CSV/Excel)
        Route::prefix('imports')->group(function () {
            Route::post('customers', [ImportController::class, 'customers']);
            Route::post('products', [ImportController::class, 'products']);
            Route::post('suppliers', [ImportController::class, 'suppliers']);
            Route::get('templates/{type}', [ImportController::class, 'downloadTemplate']);
        });

        // Accounting (Contabilidad)
        Route::prefix('accounting')->middleware(\App\Http\Middleware\CheckAccountingAccess::class)->group(function () {
            // Cuentas
            Route::apiResource('accounts', \App\Http\Controllers\Api\V1\Accounting\AccountingAccountController::class);

            // Asientos contables
            Route::apiResource('journal-entries', \App\Http\Controllers\Api\V1\Accounting\JournalEntryController::class);
            Route::post('journal-entries/{entry}/post', [\App\Http\Controllers\Api\V1\Accounting\JournalEntryController::class, 'postEntry']);
            Route::post('journal-entries/{entry}/void', [\App\Http\Controllers\Api\V1\Accounting\JournalEntryController::class, 'voidEntry']);

            // Centros de costo
            Route::apiResource('cost-centers', \App\Http\Controllers\Api\V1\Accounting\CostCenterController::class);

            // Presupuestos
            Route::apiResource('budgets', \App\Http\Controllers\Api\V1\Accounting\BudgetController::class);
            Route::post('budgets/{budget}/approve', [\App\Http\Controllers\Api\V1\Accounting\BudgetController::class, 'approve']);
            Route::post('budgets/{budget}/activate', [\App\Http\Controllers\Api\V1\Accounting\BudgetController::class, 'activate']);
            Route::post('budgets/{budget}/close', [\App\Http\Controllers\Api\V1\Accounting\BudgetController::class, 'close']);

            // Reportes
            Route::prefix('reports')->group(function () {
                Route::get('trial-balance', [\App\Http\Controllers\Api\V1\Accounting\AccountingReportController::class, 'trialBalance']);
                Route::get('balance-sheet', [\App\Http\Controllers\Api\V1\Accounting\AccountingReportController::class, 'balanceSheet']);
                Route::get('income-statement', [\App\Http\Controllers\Api\V1\Accounting\AccountingReportController::class, 'incomeStatement']);
                Route::get('general-ledger', [\App\Http\Controllers\Api\V1\Accounting\AccountingReportController::class, 'generalLedger']);
                Route::get('cash-flow', [\App\Http\Controllers\Api\V1\Accounting\AccountingReportController::class, 'cashFlow']);
            });

            // Formularios tributarios
            Route::prefix('tax-forms')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Accounting\TaxFormController::class, 'index']);
                Route::post('generate/{type}', [\App\Http\Controllers\Api\V1\Accounting\TaxFormController::class, 'generate']);
                Route::get('{submission}/download', [\App\Http\Controllers\Api\V1\Accounting\TaxFormController::class, 'download']);
            });

            // Períodos fiscales
            Route::prefix('fiscal-periods')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Accounting\FiscalPeriodController::class, 'index']);
                Route::post('create-year', [\App\Http\Controllers\Api\V1\Accounting\FiscalPeriodController::class, 'createYear']);
                Route::post('{period}/close', [\App\Http\Controllers\Api\V1\Accounting\FiscalPeriodController::class, 'close']);
                Route::post('{period}/lock', [\App\Http\Controllers\Api\V1\Accounting\FiscalPeriodController::class, 'lock']);
                Route::post('{period}/reopen', [\App\Http\Controllers\Api\V1\Accounting\FiscalPeriodController::class, 'reopen']);
            });
        });
    });
});

// API Key authenticated routes (for external integrations)
Route::prefix('v1/ext')->name('apikey.')->middleware(['api.key', 'api.rate'])->group(function () {
    // Documents via API Key
    Route::apiResource('documents', DocumentController::class)->only(['index', 'store', 'show']);
    Route::get('documents/{document}/ride', [DocumentController::class, 'downloadRide'])->name('documents.ride');
    Route::get('documents/{document}/xml', [DocumentController::class, 'downloadXml'])->name('documents.xml');

    // Customers via API Key
    Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update']);

    // Products via API Key
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    // Catalogs via API Key
    Route::prefix('catalogs')->group(function () {
        Route::get('identification-types', [CatalogController::class, 'identificationTypes'])->name('catalogs.id-types');
        Route::get('tax-rates', [CatalogController::class, 'taxRates'])->name('catalogs.tax-rates');
        Route::get('payment-methods', [CatalogController::class, 'paymentMethods'])->name('catalogs.payment-methods');
    });
});
