<?php

use App\Http\Controllers\Api\V1\AICategorizationController;
use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentSettingsController;
use App\Http\Controllers\Api\V1\EmissionPointController;
use App\Http\Controllers\Api\V1\Ext;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicLandingController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SriLookupController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SupplierController;
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

    // Landing pública (planes + textos de precios), sin autenticación.
    Route::get('public/landing', [PublicLandingController::class, 'show'])
        ->middleware('throttle:60,1');

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', 'tenant.active', 'throttle:api'])->group(function () {
        // Llaves de la API de integración (solo planes con acceso API).
        Route::middleware('plan.feature:api_access')->group(function () {
            Route::get('api-keys', [ApiKeyController::class, 'index']);
            Route::post('api-keys', [ApiKeyController::class, 'store']);
            Route::match(['put', 'patch'], 'api-keys/{id}', [ApiKeyController::class, 'update']);
            Route::post('api-keys/{id}/rotate', [ApiKeyController::class, 'rotate']);
            Route::delete('api-keys/{id}', [ApiKeyController::class, 'destroy']);
        });

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
            // Eliminar cuenta (requisito de App Store / Play Store).
            Route::delete('account', [AuthController::class, 'deleteAccount']);
        });

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::put('password', [ProfileController::class, 'updatePassword']);
        });

        // Document/Email settings (Plantillas de documento en el panel Next.js)
        Route::get('document-settings', [DocumentSettingsController::class, 'show']);
        Route::put('document-settings', [DocumentSettingsController::class, 'update']);

        // Companies
        Route::prefix('companies')->group(function () {
            Route::get('/', [CompanyController::class, 'index']);
            Route::post('/', [CompanyController::class, 'store']);
            Route::get('{company}', [CompanyController::class, 'show']);
            Route::put('{company}', [CompanyController::class, 'update']);
            Route::post('{company}/environment', [CompanyController::class, 'updateEnvironment']);
            Route::delete('{company}/test-documents', [CompanyController::class, 'purgeTestDocuments']);
            Route::post('{company}/switch', [CompanyController::class, 'switch']);
            Route::post('{company}/logo', [CompanyController::class, 'uploadLogo']);
            Route::delete('{company}/logo', [CompanyController::class, 'deleteLogo']);
            Route::get('{company}/branches', [CompanyController::class, 'branches']);
            Route::get('{company}/emission-points', [CompanyController::class, 'emissionPoints']);
        });

        // Onboarding (setup wizard for Next.js frontend)
        Route::prefix('onboarding')->group(function () {
            Route::get('status', [OnboardingController::class, 'status']);
            Route::post('business-type', [OnboardingController::class, 'businessType']);
            Route::post('company', [OnboardingController::class, 'company']);
            Route::post('certificate', [OnboardingController::class, 'certificate']);
            Route::post('establishment', [OnboardingController::class, 'establishment']);
            Route::get('sequentials', [OnboardingController::class, 'sequentials']);
            Route::post('sequentials', [OnboardingController::class, 'storeSequentials']);
            Route::post('complete', [OnboardingController::class, 'complete']);
        });

        // Estado de la firma electrónica (avisos de caducidad en el panel)
        Route::get('signature-status', [OnboardingController::class, 'signatureStatus']);

        // Vertical árbitros: perfil, partidos pitados y facturación 1×1
        Route::prefix('referee')->group(function () {
            Route::get('profile', [\App\Http\Controllers\Api\V1\RefereeController::class, 'profile']);
            Route::put('profile', [\App\Http\Controllers\Api\V1\RefereeController::class, 'updateProfile']);
            Route::get('matches', [\App\Http\Controllers\Api\V1\RefereeController::class, 'matches']);
            Route::post('matches', [\App\Http\Controllers\Api\V1\RefereeController::class, 'storeMatch']);
            Route::post('matches/invoice', [\App\Http\Controllers\Api\V1\RefereeController::class, 'invoice']);
            Route::post('matches/{officiatedMatch}/reactivate', [\App\Http\Controllers\Api\V1\RefereeController::class, 'reactivate']);
            Route::put('matches/{officiatedMatch}', [\App\Http\Controllers\Api\V1\RefereeController::class, 'updateMatch']);
            Route::delete('matches/{officiatedMatch}', [\App\Http\Controllers\Api\V1\RefereeController::class, 'destroyMatch']);
            Route::get('championships', [\App\Http\Controllers\Api\V1\RefereeController::class, 'championships']);
            Route::post('championships', [\App\Http\Controllers\Api\V1\RefereeController::class, 'storeChampionship']);
            Route::get('clubs', [\App\Http\Controllers\Api\V1\RefereeController::class, 'clubs']);
            Route::post('clubs', [\App\Http\Controllers\Api\V1\RefereeController::class, 'storeClub']);
            Route::get('catalog-requests', [\App\Http\Controllers\Api\V1\RefereeController::class, 'catalogRequests']);
            Route::post('catalog-requests', [\App\Http\Controllers\Api\V1\RefereeController::class, 'storeCatalogRequest']);
            Route::get('report', [\App\Http\Controllers\Api\V1\RefereeController::class, 'report']);
            Route::get('report/export', [\App\Http\Controllers\Api\V1\RefereeController::class, 'reportExport']);
        });

        // Consulta pública del catastro del SRI (autocompletar datos por RUC/cédula)
        Route::get('sri/ruc/{ruc}', [SriLookupController::class, 'ruc']);
        Route::get('sri/identification/{identification}', [SriLookupController::class, 'identification']);
        Route::post('sri/import-establishments', [SriLookupController::class, 'importEstablishments']);

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::get('readiness', [DashboardController::class, 'readiness']);
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
            // Streaming directo (móvil): sirve el archivo por el dominio público.
            Route::get('{document}/ride-file', [DocumentController::class, 'streamRide']);
            Route::get('{document}/xml-file', [DocumentController::class, 'streamXml']);
            Route::post('{document}/resend-email', [DocumentController::class, 'resendEmail']);
            Route::get('{document}/status', [DocumentController::class, 'checkStatus']);
            Route::get('{document}/payments', [\App\Http\Controllers\Api\V1\DocumentPaymentController::class, 'index']);
            Route::post('{document}/payments', [\App\Http\Controllers\Api\V1\DocumentPaymentController::class, 'store']);
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

        // Reports. ATS está incluido en todos los planes; los reportes
        // avanzados solo desde Negocio (plan.feature:advanced_reports).
        Route::prefix('reports')->group(function () {
            Route::get('ats', [ReportController::class, 'ats']);

            Route::middleware('plan.feature:advanced_reports')->group(function () {
                Route::get('dashboard', [ReportController::class, 'dashboard']);
                Route::get('sales', [ReportController::class, 'sales']);
                Route::get('sales/export', [ReportController::class, 'salesExport']);
                Route::get('taxes', [ReportController::class, 'taxes']);
                Route::get('tax-summary', [ReportController::class, 'taxSummary']);
                Route::get('top-customers', [ReportController::class, 'topCustomers']);
                Route::get('top-products', [ReportController::class, 'topProducts']);
                Route::get('documents-by-status', [ReportController::class, 'documentsByStatus']);
                Route::get('comparison', [ReportController::class, 'comparison']);
                Route::get('withholdings', [ReportController::class, 'withholdings']);
            });
        });

        // Inventory (solo planes con has_inventory)
        Route::prefix('inventory')->middleware('plan.feature:inventory')->group(function () {
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

        // POS (Punto de Venta) - solo planes con has_pos
        Route::prefix('pos')->middleware('plan.feature:pos')->group(function () {
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

            // Saldos iniciales (asiento de apertura)
            Route::post('opening-balance', [\App\Http\Controllers\Api\V1\Accounting\OpeningBalanceController::class, 'store']);

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

        // Quotes
        Route::apiResource('quotes', \App\Http\Controllers\Api\V1\QuoteController::class);
        Route::post('quotes/{quote}/send', [\App\Http\Controllers\Api\V1\QuoteController::class, 'send']);
        Route::post('quotes/{quote}/accept', [\App\Http\Controllers\Api\V1\QuoteController::class, 'accept']);
        Route::post('quotes/{quote}/reject', [\App\Http\Controllers\Api\V1\QuoteController::class, 'reject']);

        // Received documents (compras electrónicas)
        Route::apiResource('received-documents', \App\Http\Controllers\Api\V1\ReceivedDocumentController::class);

        // Personal expenses
        Route::apiResource('personal-expenses', \App\Http\Controllers\Api\V1\PersonalExpenseController::class);
        Route::get('personal-expenses-summary', [\App\Http\Controllers\Api\V1\PersonalExpenseController::class, 'summary']);
        Route::get('personal-expenses-budget', [\App\Http\Controllers\Api\V1\PersonalExpenseController::class, 'budget']);
        Route::put('personal-expenses-budget', [\App\Http\Controllers\Api\V1\PersonalExpenseController::class, 'updateBudget']);

        // Recurring invoices (solo planes con has_recurring_invoices)
        Route::middleware('plan.feature:recurring_invoices')->group(function () {
            Route::apiResource('recurring-invoices', \App\Http\Controllers\Api\V1\RecurringInvoiceController::class);
            Route::post('recurring-invoices/{recurring_invoice}/pause', [\App\Http\Controllers\Api\V1\RecurringInvoiceController::class, 'pause']);
            Route::post('recurring-invoices/{recurring_invoice}/resume', [\App\Http\Controllers\Api\V1\RecurringInvoiceController::class, 'resume']);
        });

        // Support tickets
        Route::get('support/tickets', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'index']);
        Route::post('support/tickets', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'store']);
        Route::get('support/tickets/{ticket}', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'show']);
        Route::post('support/tickets/{ticket}/reply', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'reply']);
        Route::post('support/tickets/{ticket}/close', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'close']);
        Route::post('support/tickets/{ticket}/reopen', [\App\Http\Controllers\Api\V1\SupportTicketController::class, 'reopen']);
    });

    // Descarga pública del RIDE/XML con token HMAC (la validación del token
    // reemplaza a la auth): sirve el PDF/XML al navegador por el dominio
    // público sin exponer el almacenamiento interno.
    Route::get('public/documents/{document}/ride', [DocumentController::class, 'streamRidePublic'])
        ->name('documents.ride.public');
    Route::get('public/documents/{document}/xml', [DocumentController::class, 'streamXmlPublic'])
        ->name('documents.xml.public');
});

// ============================================================================
// API de integración (llaves fec_…): /api/v1/ext — documentación en /docs/api
// ============================================================================
Route::prefix('v1/ext')->name('ext.')->middleware(['api.key', 'api.rate'])->group(function () {
    Route::get('me', [Ext\MeController::class, 'show'])->name('me');
    Route::get('companies', [Ext\CompanyController::class, 'index'])
        ->middleware('api.scope:documents:read')->name('companies');

    // Documentos
    Route::middleware('api.scope:documents:read')->group(function () {
        Route::get('documents', [Ext\DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [Ext\DocumentController::class, 'show'])->name('documents.show');
        Route::get('documents/{document}/status', [Ext\DocumentController::class, 'status'])->name('documents.status');
        Route::get('documents/{document}/ride', [Ext\DocumentController::class, 'ride'])->name('documents.ride');
        Route::get('documents/{document}/xml', [Ext\DocumentController::class, 'xml'])->name('documents.xml');
    });
    Route::middleware('api.scope:documents:write')->group(function () {
        Route::post('documents', [Ext\DocumentController::class, 'store'])->name('documents.store');
        Route::post('documents/{document}/send', [Ext\DocumentController::class, 'send'])->name('documents.send');
        Route::post('documents/{document}/void', [Ext\DocumentController::class, 'void'])->name('documents.void');
        Route::post('documents/{document}/email', [Ext\DocumentController::class, 'email'])->name('documents.email');
    });

    // Clientes
    Route::middleware('api.scope:customers:read')->group(function () {
        Route::get('customers', [Ext\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/lookup', [Ext\CustomerController::class, 'lookup'])->name('customers.lookup');
        Route::get('customers/{customer}', [Ext\CustomerController::class, 'show'])->name('customers.show');
    });
    Route::middleware('api.scope:customers:write')->group(function () {
        Route::post('customers', [Ext\CustomerController::class, 'store'])->name('customers.store');
        Route::match(['put', 'patch'], 'customers/{customer}', [Ext\CustomerController::class, 'update'])->name('customers.update');
    });

    // Productos
    Route::middleware('api.scope:products:read')->group(function () {
        Route::get('products', [Ext\ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [Ext\ProductController::class, 'show'])->name('products.show');
    });
    Route::middleware('api.scope:products:write')->group(function () {
        Route::post('products', [Ext\ProductController::class, 'store'])->name('products.store');
        Route::match(['put', 'patch'], 'products/{product}', [Ext\ProductController::class, 'update'])->name('products.update');
    });

    // Catálogos del SRI (alcance implícito en toda llave)
    Route::prefix('catalogs')->name('catalogs.')->middleware('api.scope:catalogs:read')->group(function () {
        Route::get('identification-types', [CatalogController::class, 'identificationTypes'])->name('identification-types');
        Route::get('document-types', [CatalogController::class, 'documentTypes'])->name('document-types');
        Route::get('payment-methods', [CatalogController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('tax-rates', [CatalogController::class, 'taxRates'])->name('tax-rates');
        Route::get('retention-codes', [CatalogController::class, 'retentionCodes'])->name('retention-codes');
    });
});
