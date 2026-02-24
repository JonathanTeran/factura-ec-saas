<x-layouts.auth title="Verificación en dos pasos" subtitle="Ingresa tu código de autenticación">
    <div x-data="{ useRecoveryCode: false }">
        {{-- Icon --}}
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 dark:bg-teal-900/30">
                <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
        </div>

        {{-- TOTP Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-5" x-show="!useRecoveryCode">
            @csrf

            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-4">
                Ingresa el código de 6 dígitos de tu aplicación de autenticación.
            </p>

            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Código de autenticación
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                    </div>
                    <input type="text" name="code" id="code" autofocus autocomplete="one-time-code" inputmode="numeric"
                           placeholder="000000" maxlength="6"
                           class="block w-full rounded-xl border-0 py-3 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm text-center tracking-[0.5em] font-mono transition-shadow">
                </div>
                @error('code')
                    <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/30 hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all">
                Verificar
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </form>

        {{-- Recovery Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-5" x-show="useRecoveryCode" x-cloak>
            @csrf

            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-4">
                Ingresa uno de tus códigos de recuperación de emergencia.
            </p>

            <div>
                <label for="recovery_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Código de recuperación
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <input type="text" name="recovery_code" id="recovery_code" autocomplete="one-time-code"
                           placeholder="xxxxx-xxxxx"
                           class="block w-full rounded-xl border-0 py-3 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm font-mono transition-shadow">
                </div>
                @error('recovery_code')
                    <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/30 hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all">
                Verificar
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </form>

        {{-- Toggle between modes --}}
        <div class="mt-5 text-center">
            <button type="button" @click="useRecoveryCode = !useRecoveryCode"
                    class="text-sm font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors">
                <span x-show="!useRecoveryCode">Usar código de recuperación</span>
                <span x-show="useRecoveryCode" x-cloak>Usar código de autenticación</span>
            </button>
        </div>
    </div>
</x-layouts.auth>
