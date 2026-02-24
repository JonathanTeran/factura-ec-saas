<x-layouts.auth>
    <x-slot name="subtitle">Selecciona una empresa</x-slot>

    <div>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Tu identificacion esta asociada a varias empresas. Selecciona a cual deseas acceder:
        </p>

        <div class="space-y-3">
            @foreach($customers as $customer)
            <form method="POST" action="{{ route('portal.login.send') }}">
                @csrf
                <input type="hidden" name="input" value="{{ $input }}">
                <input type="hidden" name="tenant_id" value="{{ $customer->tenant_id }}">
                <button type="submit"
                        class="flex w-full items-center rounded-lg border border-gray-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50 dark:border-gray-600 dark:hover:border-blue-500 dark:hover:bg-blue-900/20">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $customer->tenant->name ?? 'Empresa' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $customer->email }}
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </form>
            @endforeach
        </div>
    </div>

    <x-slot name="footer">
        <a href="{{ route('portal.login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
            Volver al inicio
        </a>
    </x-slot>
</x-layouts.auth>
