<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // All factories live in Database\Factories\ (flat), even for models in App\Models\Tenant\
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $modelBaseName = class_basename($modelName);

            return 'Database\\Factories\\' . $modelBaseName . 'Factory';
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure application rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        // 60 requests per minute for authenticated API routes
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 10 attempts per minute for login
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower($request->input('email', '')) . '|' . $request->ip();

            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiados intentos de inicio de sesión. Intenta de nuevo en un minuto.',
                    'errors' => [],
                ], 429);
            });
        });

        // 3 requests per hour for magic link / password reset
        RateLimiter::for('magic-link', function (Request $request) {
            $key = strtolower($request->input('email', '')) . '|' . $request->ip();

            return Limit::perHour(3)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Has excedido el limite de solicitudes. Intenta de nuevo mas tarde.',
                    'errors' => [],
                ], 429);
            });
        });
    }
}
