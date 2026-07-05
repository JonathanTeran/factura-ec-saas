<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // El enlace de recuperación debe abrir la página de Next.js
        // (/reset-password) con token y email como query params, que es lo
        // que espera el frontend. Sin esto, el link por defecto usa el token
        // en el path (/reset-password/{token}) y cae en 404.
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(
            function ($notifiable, string $token) {
                $base = rtrim((string) config('app.url'), '/');
                $email = urlencode($notifiable->getEmailForPasswordReset());

                return "{$base}/reset-password?token={$token}&email={$email}";
            }
        );

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Auth Views
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));

        // Redirect after login
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && \Hash::check($request->password, $user->password)) {
                if (!$user->is_active) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => ['Tu cuenta ha sido desactivada.'],
                    ]);
                }

                if ($user->tenant && !$user->tenant->isAccessible()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => ['Tu cuenta de empresa está suspendida.'],
                    ]);
                }

                $user->updateLastLogin();
                return $user;
            }

            return null;
        });
    }
}
