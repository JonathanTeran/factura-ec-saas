<div>
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('tenant.settings.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Configuración
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="size-5 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500 dark:text-gray-400">Empresas</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Empresas</h1>
    </div>

    <div class="space-y-6">
        @foreach($companies as $company)
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $company->trade_name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">RUC: {{ $company->ruc }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $company->sri_environment === 'production' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                            {{ $company->sri_environment === 'production' ? 'Producción' : 'Pruebas' }}
                        </span>
                        @if($company->hasValidSignature())
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                            Firma válida
                        </span>
                        @else
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                            Sin firma
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Razón Social</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $company->business_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $company->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $company->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vence Firma</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $company->signature_expires_at?->format('d/m/Y') ?? '-' }}</dd>
                    </div>
                </dl>

                @if($company->branches->count() > 0)
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Establecimientos</h4>
                    <ul class="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($company->branches as $branch)
                        <li class="py-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $branch->code }} - {{ $branch->name }}</span>
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $branch->address }}</span>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $branch->emissionPoints->count() }} punto(s) de emisión
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        @if($companies->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
            <svg class="mx-auto size-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay empresas configuradas</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configura tu primera empresa para comenzar a facturar.</p>
        </div>
        @endif
    </div>
</div>
