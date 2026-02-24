<x-layouts.auth>
    <x-slot name="subtitle">Revisa tu correo</x-slot>

    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Si tu email o identificacion esta registrado en nuestro sistema, recibiras un enlace de acceso en tu correo electronico.
        </p>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            El enlace es valido por {{ config('portal.token_expiry_hours', 24) }} horas y solo puede usarse una vez.
        </p>
    </div>

    <x-slot name="footer">
        <a href="{{ route('portal.login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
            Volver al inicio
        </a>
    </x-slot>
</x-layouts.auth>
