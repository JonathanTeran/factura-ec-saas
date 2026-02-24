<?php

namespace App\Livewire\Panel\Settings;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileSettings extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public $avatar;

    // 2FA
    public bool $showingQrCode = false;
    public bool $showingRecoveryCodes = false;
    public string $twoFactorCode = '';
    public string $confirmPassword = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = auth()->user();

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Perfil actualizado correctamente.',
        ]);
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Ingresa la nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    public function deleteAvatar(): void
    {
        $user = auth()->user();

        if ($user->avatar) {
            \Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Foto de perfil eliminada.',
            ]);
        }
    }

    // ==================== 2FA ====================

    public function getTwoFactorEnabledProperty(): bool
    {
        return !is_null(auth()->user()->two_factor_confirmed_at);
    }

    public function enableTwoFactor(): void
    {
        $this->validate([
            'confirmPassword' => 'required|current_password',
        ], [
            'confirmPassword.required' => 'Ingresa tu contraseña para continuar.',
            'confirmPassword.current_password' => 'La contraseña no es correcta.',
        ]);

        app(EnableTwoFactorAuthentication::class)(auth()->user());

        $this->showingQrCode = true;
        $this->showingRecoveryCodes = false;
        $this->confirmPassword = '';
    }

    public function confirmTwoFactor(): void
    {
        $this->validate([
            'twoFactorCode' => 'required|string|size:6',
        ], [
            'twoFactorCode.required' => 'Ingresa el codigo de verificacion.',
            'twoFactorCode.size' => 'El codigo debe tener 6 digitos.',
        ]);

        app(ConfirmTwoFactorAuthentication::class)(auth()->user(), $this->twoFactorCode);

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = true;
        $this->twoFactorCode = '';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Autenticacion de dos factores activada correctamente.',
        ]);
    }

    public function disableTwoFactor(): void
    {
        $this->validate([
            'confirmPassword' => 'required|current_password',
        ], [
            'confirmPassword.required' => 'Ingresa tu contraseña para continuar.',
            'confirmPassword.current_password' => 'La contraseña no es correcta.',
        ]);

        app(DisableTwoFactorAuthentication::class)(auth()->user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;
        $this->confirmPassword = '';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Autenticacion de dos factores desactivada.',
        ]);
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
        $this->showingQrCode = false;
    }

    public function regenerateRecoveryCodes(): void
    {
        app(GenerateNewRecoveryCodes::class)(auth()->user());

        $this->showingRecoveryCodes = true;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Codigos de recuperacion regenerados.',
        ]);
    }

    public function getRecoveryCodesProperty(): array
    {
        return json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) ?? [];
    }

    public function render()
    {
        return view('livewire.panel.settings.profile-settings')
            ->layout('layouts.tenant', ['title' => 'Perfil']);
    }
}
