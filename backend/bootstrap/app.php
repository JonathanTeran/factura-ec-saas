<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware applied to all requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'tenant.scope' => \App\Http\Middleware\SetTenantScope::class,
            'plan.limits' => \App\Http\Middleware\CheckPlanLimits::class,
            'portal.auth' => \App\Http\Middleware\CustomerPortalAuth::class,
            'onboarding' => \App\Http\Middleware\CheckOnboarding::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'api.key' => \App\Http\Middleware\VerifyApiKey::class,
            'api.rate' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetTenantScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('admin*') && auth()->check()) {
                return redirect()->route('panel.dashboard');
            }
        });

        // SRI exceptions
        $exceptions->render(function (\App\Exceptions\SriRejectionException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'sri_rejection',
                    'message' => $e->getMessage(),
                    'sri_errors' => $e->errors,
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\SriCommunicationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'sri_unavailable',
                    'message' => $e->getMessage(),
                ], 503);
            }
        });

        $exceptions->render(function (\App\Exceptions\SriException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'sri_error',
                    'message' => $e->getMessage(),
                ], 500);
            }
        });

        // Certificate exceptions
        $exceptions->render(function (\App\Exceptions\CertificateNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'certificate_not_found',
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\CertificateExpiredException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'certificate_expired',
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\CertificateException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'certificate_error',
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        // Payment exceptions
        $exceptions->render(function (\App\Exceptions\PaymentFailedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'payment_failed',
                    'message' => $e->getMessage(),
                    'gateway' => $e->gateway,
                ], 402);
            }
        });

        $exceptions->render(function (\App\Exceptions\PaymentGatewayException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'payment_gateway_error',
                    'message' => $e->getMessage(),
                ], 502);
            }
        });

        $exceptions->render(function (\App\Exceptions\PaymentException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'payment_error',
                    'message' => $e->getMessage(),
                ], 402);
            }
        });

        // Plan/Feature exceptions
        $exceptions->render(function (\App\Exceptions\PlanLimitExceededException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'plan_limit_exceeded',
                    'message' => $e->getMessage(),
                    'resource' => $e->resource,
                    'limit' => $e->limit,
                    'used' => $e->used,
                ], 403);
            }
            return redirect()->route('panel.settings.billing')
                ->with('error', $e->getMessage());
        });

        $exceptions->render(function (\App\Exceptions\FeatureNotAvailableException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'feature_not_available',
                    'message' => $e->getMessage(),
                    'feature' => $e->feature,
                ], 403);
            }
            return redirect()->route('panel.settings.billing')
                ->with('error', $e->getMessage());
        });

        // Tenant exceptions
        $exceptions->render(function (\App\Exceptions\TenantInactiveException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'tenant_inactive',
                    'message' => $e->getMessage(),
                ], 403);
            }
        });

        // Model not found
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                    'errors' => [],
                ], 404);
            }
        });

        // Validation
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Authentication
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                    'errors' => [],
                ], 401);
            }
        });

        // Rate limiting
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.',
                    'errors' => [],
                ], 429);
            }
        });

        // Catch-all for unexpected errors (must be last)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() && !app()->hasDebugModeEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor',
                    'errors' => [],
                ], 500);
            }
        });
    })->create();
