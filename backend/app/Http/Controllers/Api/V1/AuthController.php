<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @tags Autenticación
 */
class AuthController extends ApiController
{
    /**
     * Iniciar sesión
     *
     * Autentica un usuario y retorna un token de acceso Bearer.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            return $this->forbidden('Tu cuenta ha sido desactivada.');
        }

        if ($user->tenant && !$user->tenant->isAccessible()) {
            return $this->forbidden('Tu cuenta de empresa no está activa.');
        }

        // Revoke previous tokens if needed
        if ($request->boolean('revoke_previous', false)) {
            $user->tokens()->delete();
        }

        $token = $user->createToken(
            $request->device_name ?? 'mobile-app',
            ['*'],
            now()->addDays(30)
        );

        return $this->success([
            'user' => new UserResource($user->load('tenant')),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Inicio de sesión exitoso');
    }

    /**
     * Registrar nuevo usuario
     *
     * Crea una nueva cuenta con un tenant y periodo de prueba de 14 días.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = DB::transaction(function () use ($request) {
            // Default to the cheapest active plan (nullable FK, safe if none exist)
            $defaultPlan = Plan::where('is_active', true)->orderBy('sort_order')->first();

            // Create tenant (uuid + referral_code are set by the model on creating)
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name) . '-' . Str::random(4),
                'owner_email' => $request->email,
                'status' => TenantStatus::ACTIVE,
                'trial_ends_at' => null,
                'current_plan_id' => $defaultPlan?->id,
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
                'has_webhooks' => $defaultPlan?->has_webhooks ?? false,
                'has_ai_categorization' => $defaultPlan?->has_ai_categorization ?? false,
                'has_client_portal' => $defaultPlan?->has_client_portal ?? false,
                'has_multi_currency' => $defaultPlan?->has_multi_currency ?? false,
                'has_thermal_printer' => $defaultPlan?->has_thermal_printer ?? false,
                'documents_this_month' => 0,
                'documents_month_reset_at' => now()->startOfMonth(),
            ]);

            // Create user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => UserRole::TENANT_OWNER,
                'is_active' => true,
                // Constancia de aceptación de Términos + Privacidad.
                'terms_accepted_at' => now(),
            ]);

            $tenant->update(['owner_id' => $user->id]);

            $token = $user->createToken(
                $request->device_name ?? 'mobile-app',
                ['*'],
                now()->addDays(30)
            );

            return [$user, $token];
        });

        // Correo de bienvenida al nuevo usuario con los pasos pendientes
        // (configuración asistida). Encolado; lo procesa Horizon.
        $user->notify(new \App\Notifications\WelcomeTenantNotification($user));

        return $this->created([
            'user' => new UserResource($user->load('tenant')),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Registro exitoso. Bienvenido a AmePhia Facturacion.');
    }

    /**
     * Cerrar sesión
     *
     * Revoca el token de acceso actual.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada exitosamente');
    }

    /**
     * Eliminar cuenta
     *
     * Elimina la cuenta del usuario (requisito de App Store y Play Store).
     * Requiere confirmar la contraseña. Cancela la suscripción, revoca todos
     * los tokens y elimina (soft-delete) el usuario y su cuenta. Los
     * comprobantes fiscales se conservan el tiempo que exige la ley del SRI y
     * luego se purgan definitivamente.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Confirma tu contraseña para eliminar la cuenta.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return $this->error('La contraseña es incorrecta.', 422);
        }

        $tenant = $user->tenant;

        DB::transaction(function () use ($user, $tenant) {
            // Cancelar la suscripción activa si existe.
            $tenant?->activeSubscription?->cancel('Cuenta eliminada por el usuario');

            // Revocar todos los tokens de acceso del usuario.
            $user->tokens()->delete();

            // Soft-delete: la cuenta queda inaccesible de inmediato; los datos
            // fiscales se conservan por retención legal y se purgan después.
            $user->update(['is_active' => false]);
            $user->delete();
            $tenant?->update(['status' => TenantStatus::CANCELLED]);
            $tenant?->delete();
        });

        // Aviso a la administración (comunicar siempre los eventos importantes).
        try {
            \App\Services\Notification\NotificationService::notifyConfiguredAdmins(
                new \App\Notifications\AccountDeletedAdminNotification(
                    $user->name,
                    $user->email,
                    $tenant?->name,
                ),
            );
        } catch (\Throwable) {
            // El borrado no debe fallar por un problema de notificación.
        }

        return $this->success(
            null,
            'Tu cuenta fue eliminada. Los comprobantes fiscales se conservan el '
            . 'tiempo que exige la ley y luego se eliminan definitivamente.'
        );
    }

    /**
     * Renovar token
     *
     * Revoca el token actual y genera uno nuevo.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken(
            $request->device_name ?? 'mobile-app',
            ['*'],
            now()->addDays(30)
        );

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Token renovado exitosamente');
    }

    /**
     * Obtener usuario actual
     *
     * Retorna los datos del usuario autenticado con su tenant y suscripción.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['tenant.plan', 'tenant.currentSubscription']);

        return $this->success([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Solicitar restablecimiento de contraseña
     *
     * Envía un correo con el enlace de recuperación.
     *
     * @unauthenticated
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Se ha enviado un enlace de recuperación a tu correo.');
        }

        return $this->error('No pudimos encontrar un usuario con ese correo.', 400);
    }

    /**
     * Restablecer contraseña
     *
     * Establece una nueva contraseña usando el token de recuperación.
     *
     * @unauthenticated
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            // Misma política central que registro/cambio. El import Password
            // de este archivo es la FACHADA de reseteo; la regla va calificada.
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Tu contraseña ha sido restablecida.');
        }

        return $this->error('El token de recuperación es inválido o ha expirado.', 400);
    }
}
