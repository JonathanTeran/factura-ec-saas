<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.settings.index') }}"
           class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Mi Perfil
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Actualiza tu información personal y contraseña
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Sidebar - Avatar --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
                <div class="text-center">
                    {{-- Avatar actual --}}
                    <div class="relative mx-auto mb-4 h-32 w-32">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="h-32 w-32 rounded-full object-cover ring-4 ring-slate-100 dark:ring-slate-700">
                            <button wire:click="deleteAvatar" type="button"
                                    class="absolute -right-1 -top-1 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-lg hover:bg-red-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @else
                            <div class="flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-4xl font-bold text-white ring-4 ring-slate-100 dark:ring-slate-700">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>

                    {{-- Upload nuevo avatar --}}
                    <div class="mt-4">
                        <label for="avatar" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            Cambiar foto
                        </label>
                        <input wire:model="avatar" type="file" id="avatar" accept="image/*" class="sr-only">
                    </div>

                    @if($avatar)
                        <div class="mt-3">
                            <p class="text-sm text-slate-500">Nuevo archivo: {{ $avatar->getClientOriginalName() }}</p>
                        </div>
                    @endif

                    @error('avatar')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Información personal --}}
            <form wire:submit="updateProfile" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
                <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Información personal</h3>

                <div class="grid gap-6 sm:grid-cols-2">
                    {{-- Nombre --}}
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name" type="text" id="name"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="email" type="email" id="email"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Teléfono
                        </label>
                        <input wire:model="phone" type="tel" id="phone"
                               placeholder="0999999999"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                        <svg wire:loading wire:target="updateProfile" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="updateProfile">Guardar cambios</span>
                        <span wire:loading wire:target="updateProfile">Guardando...</span>
                    </button>
                </div>
            </form>

            {{-- Cambiar contraseña --}}
            <form wire:submit="updatePassword" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
                <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Cambiar contraseña</h3>

                <div class="grid gap-6 sm:grid-cols-2">
                    {{-- Contraseña actual --}}
                    <div class="sm:col-span-2">
                        <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Contraseña actual <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="current_password" type="password" id="current_password"
                               autocomplete="current-password"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nueva contraseña --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Nueva contraseña <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="password" type="password" id="password"
                               autocomplete="new-password"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirmar contraseña --}}
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Confirmar contraseña <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="password_confirmation" type="password" id="password_confirmation"
                               autocomplete="new-password"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-slate-700 dark:hover:bg-slate-600 dark:focus:ring-offset-slate-900">
                        <svg wire:loading wire:target="updatePassword" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="updatePassword">Actualizar contraseña</span>
                        <span wire:loading wire:target="updatePassword">Actualizando...</span>
                    </button>
                </div>
            </form>

            {{-- Autenticación de dos factores --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Autenticación de dos factores</h3>
                <p class="mt-1 mb-4 text-sm text-slate-500 dark:text-slate-400">
                    La autenticación de dos factores agrega una capa adicional de seguridad a tu cuenta.
                </p>

                @if(!$this->twoFactorEnabled)
                    {{-- 2FA no activada --}}
                    <div class="space-y-4">
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            Cuando la autenticación de dos factores esté habilitada, se te pedirá un código seguro y aleatorio durante la autenticación. Puedes obtener este código desde la aplicación Google Authenticator de tu teléfono.
                        </p>

                        @if($showingQrCode)
                            {{-- QR Code --}}
                            <div class="space-y-4">
                                <div class="flex justify-center rounded-xl bg-white p-4 dark:bg-slate-900">
                                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                                </div>

                                <p class="text-center text-sm text-slate-600 dark:text-slate-300">
                                    Escanea este código QR con tu aplicación de autenticación
                                </p>

                                <div>
                                    <label for="twoFactorCode" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Código de verificación
                                    </label>
                                    <input wire:model="twoFactorCode" type="text" id="twoFactorCode"
                                           maxlength="6" inputmode="numeric" placeholder="000000"
                                           class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                    @error('twoFactorCode')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button wire:click="confirmTwoFactor" type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                                        <svg wire:loading wire:target="confirmTwoFactor" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="confirmTwoFactor">Confirmar</span>
                                        <span wire:loading wire:target="confirmTwoFactor">Confirmando...</span>
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Activar 2FA --}}
                            <div class="space-y-4">
                                <div>
                                    <label for="confirmPassword" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Confirma tu contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="confirmPassword" type="password" id="confirmPassword"
                                           autocomplete="current-password"
                                           class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                    @error('confirmPassword')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button wire:click="enableTwoFactor" type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                                        <svg wire:loading wire:target="enableTwoFactor" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="enableTwoFactor">Activar 2FA</span>
                                        <span wire:loading wire:target="enableTwoFactor">Activando...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- 2FA activada --}}
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            Autenticación de dos factores activada
                        </div>

                        @if($showingRecoveryCodes)
                            {{-- Códigos de recuperación --}}
                            <div class="space-y-4">
                                <p class="text-sm text-slate-600 dark:text-slate-300">
                                    Guarda estos códigos de recuperación en un lugar seguro. Pueden ser utilizados para recuperar el acceso a tu cuenta si pierdes tu dispositivo de autenticación de dos factores.
                                </p>

                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($this->recoveryCodes as $code)
                                        <div class="rounded-lg bg-slate-100 px-3 py-2 font-mono text-sm text-slate-900 dark:bg-slate-900/50 dark:text-slate-300">
                                            {{ $code }}
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-3">
                                    <button wire:click="regenerateRecoveryCodes" type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                        Regenerar códigos
                                    </button>
                                    <button wire:click="$set('showingRecoveryCodes', false)" type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        @else
                            <div>
                                <button wire:click="showRecoveryCodes" type="button"
                                        class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                    Ver códigos de recuperación
                                </button>
                            </div>
                        @endif

                        {{-- Separador --}}
                        <div class="border-t border-slate-200 dark:border-slate-700"></div>

                        {{-- Desactivar 2FA --}}
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Desactivar autenticación de dos factores</h4>

                            <div>
                                <label for="confirmPasswordDisable" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Confirma tu contraseña <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="confirmPassword" type="password" id="confirmPasswordDisable"
                                       autocomplete="current-password"
                                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                @error('confirmPassword')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <button wire:click="disableTwoFactor" type="button"
                                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition-all hover:bg-red-700 hover:shadow-xl hover:shadow-red-500/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                                    <svg wire:loading wire:target="disableTwoFactor" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="disableTwoFactor">Desactivar 2FA</span>
                                    <span wire:loading wire:target="disableTwoFactor">Desactivando...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Información de sesión --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
                <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Información de cuenta</h3>

                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Miembro desde</dt>
                        <dd class="text-sm font-medium text-slate-900 dark:text-white">
                            {{ auth()->user()->created_at->format('d/m/Y') }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Última actualización</dt>
                        <dd class="text-sm font-medium text-slate-900 dark:text-white">
                            {{ auth()->user()->updated_at->diffForHumans() }}
                        </dd>
                    </div>
                    @if(auth()->user()->email_verified_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">Email verificado</dt>
                            <dd class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Verificado
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
