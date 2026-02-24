<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
        // Create tenant
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'slug' => \Str::slug($request->company_name) . '-' . \Str::random(4),
            'email' => $request->email,
            'plan_id' => 1, // Default to Starter plan
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'tenant_owner',
            'is_active' => true,
        ]);

        $tenant->update(['owner_id' => $user->id]);

        $token = $user->createToken(
            $request->device_name ?? 'mobile-app',
            ['*'],
            now()->addDays(30)
        );

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
            'password' => ['required', 'min:8', 'confirmed'],
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
