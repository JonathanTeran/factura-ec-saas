<x-layouts.auth title="Crear Cuenta" subtitle="Regístrate gratis y comienza a facturar">
    @if(request('plan'))
        @php
            $selectedPlan = \App\Models\Billing\Plan::where('slug', request('plan'))->where('is_active', true)->first();
        @endphp
        @if($selectedPlan)
            <div class="rounded-xl bg-teal-50 dark:bg-teal-900/20 ring-1 ring-teal-200/60 dark:ring-teal-700/40 px-4 py-3 flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800/40">
                    <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-teal-800 dark:text-teal-300">Plan {{ $selectedPlan->name }}</p>
                    <p class="text-xs text-teal-600 dark:text-teal-400">
                        ${{ number_format($selectedPlan->price_monthly, 2) }}/mes
                    </p>
                </div>
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ showPassword: false, showPasswordConfirm: false }">
        @csrf

        @if(request('plan'))
            <input type="hidden" name="plan" value="{{ request('plan') }}">
        @endif

        @if(request('ref'))
            <input type="hidden" name="ref" value="{{ request('ref') }}">
        @endif

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Nombre completo
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                       placeholder="Tu nombre completo"
                       class="block w-full rounded-xl border-0 py-3 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm transition-shadow">
            </div>
            @error('name')
                <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Company Name --}}
        <div>
            <label for="company_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Nombre de la empresa
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M3.75 3v18m4.5-18v18m4.5-18v18m4.5-18v18M5.25 4.5h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75m3.75-12h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75m3.75-12h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75" />
                    </svg>
                </div>
                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                       placeholder="Nombre de tu empresa"
                       class="block w-full rounded-xl border-0 py-3 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm transition-shadow">
            </div>
            @error('company_name')
                <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Correo electrónico
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </div>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       placeholder="tu@email.com"
                       class="block w-full rounded-xl border-0 py-3 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm transition-shadow">
            </div>
            @error('email')
                <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Contraseña
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                       placeholder="Mínimo 8 caracteres"
                       class="block w-full rounded-xl border-0 py-3 pl-11 pr-12 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm transition-shadow">
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Confirmation --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Confirmar contraseña
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <input :type="showPasswordConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                       placeholder="Repite tu contraseña"
                       class="block w-full rounded-xl border-0 py-3 pl-11 pr-12 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-teal-500 text-sm transition-shadow">
                <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg x-show="!showPasswordConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPasswordConfirm" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Terms --}}
        <div class="flex items-start gap-2">
            <input type="checkbox" name="terms" id="terms" required
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-offset-slate-900 transition-colors">
            <label for="terms" class="text-sm text-slate-600 dark:text-slate-400">
                Acepto los <a href="#" class="font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors">términos y condiciones</a>
                y la <a href="#" class="font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors">política de privacidad</a>
            </label>
        </div>
        @error('terms')
            <p class="-mt-3 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror

        {{-- Submit button --}}
        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/30 hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all">
            Crear cuenta
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </button>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500">
            Incluye 14 días de prueba gratis. No se requiere tarjeta de crédito.
        </p>
    </form>

    <x-slot:footer>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors">
                Inicia sesión
            </a>
        </p>
    </x-slot:footer>
</x-layouts.auth>
