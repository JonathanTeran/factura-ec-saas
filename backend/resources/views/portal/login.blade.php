<x-layouts.auth>
    <x-slot name="subtitle">Portal de Documentos</x-slot>

    <form method="POST" action="{{ route('portal.login.send') }}" class="space-y-6">
        @csrf

        <div>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Ingresa tu email, cedula o RUC para acceder a tus documentos electronicos.
            </p>
        </div>

        <div>
            <label for="input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email, Cedula o RUC
            </label>
            <div class="mt-1">
                <input
                    id="input"
                    name="input"
                    type="text"
                    autocomplete="email"
                    required
                    value="{{ old('input') }}"
                    placeholder="correo@ejemplo.com o 1712345678"
                    class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                />
            </div>
        </div>

        @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/20">
            <div class="text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        </div>
        @endif

        <div>
            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Enviar enlace de acceso
            </button>
        </div>
    </form>

    <x-slot name="footer">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Te enviaremos un enlace seguro a tu correo electronico para acceder.
        </p>
    </x-slot>
</x-layouts.auth>
