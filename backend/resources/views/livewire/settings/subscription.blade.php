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
                        <span class="ml-4 text-sm font-medium text-gray-500 dark:text-gray-400">Suscripción</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Suscripción</h1>
    </div>

    <!-- Current Plan -->
    <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Plan Actual</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-primary-600">{{ $tenant->plan?->name ?? 'Sin plan' }}</p>
                @if($tenant->isOnTrial())
                <p class="text-sm text-yellow-600 dark:text-yellow-400">
                    Período de prueba - Termina el {{ $tenant->trial_ends_at?->format('d/m/Y') }}
                </p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">Documentos este mes</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $tenant->documents_issued_this_month ?? 0 }}
                    <span class="text-sm font-normal text-gray-500">
                        / {{ $tenant->plan?->max_documents_per_month == -1 ? '∞' : $tenant->plan?->max_documents_per_month }}
                    </span>
                </p>
            </div>
        </div>

        @if($tenant->plan && $tenant->plan->max_documents_per_month > 0)
        <div class="mt-4">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-full rounded-full bg-primary-600"
                    style="width: {{ min(100, (($tenant->documents_issued_this_month ?? 0) / $tenant->plan->max_documents_per_month) * 100) }}%">
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Available Plans -->
    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Planes Disponibles</h3>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @foreach($plans as $plan)
        <div class="relative rounded-lg border {{ $plan->is_featured ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }} bg-white p-6 shadow dark:bg-gray-800">
            @if($plan->is_featured)
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="inline-flex rounded-full bg-primary-600 px-3 py-1 text-xs font-semibold text-white">
                    Recomendado
                </span>
            </div>
            @endif

            <div class="text-center">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>
                <div class="mt-4">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price_monthly, 2) }}</span>
                    <span class="text-gray-500 dark:text-gray-400">/mes</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    o ${{ number_format($plan->price_yearly, 2) }}/año
                </p>
            </div>

            <ul class="mt-6 space-y-3">
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <svg class="mr-2 size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ $plan->max_documents_per_month == -1 ? 'Documentos ilimitados' : $plan->max_documents_per_month . ' documentos/mes' }}
                </li>
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <svg class="mr-2 size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ $plan->max_users == -1 ? 'Usuarios ilimitados' : $plan->max_users . ' usuario(s)' }}
                </li>
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <svg class="mr-2 size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ $plan->max_companies == -1 ? 'Empresas ilimitadas' : $plan->max_companies . ' empresa(s)' }}
                </li>
                @if($plan->has_api_access)
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <svg class="mr-2 size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Acceso API
                </li>
                @endif
                @if($plan->has_inventory)
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <svg class="mr-2 size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Control de inventario
                </li>
                @endif
            </ul>

            <div class="mt-6">
                @if($tenant->plan_id === $plan->id)
                <button disabled
                    class="w-full rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    Plan actual
                </button>
                @else
                <button
                    class="w-full rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                    Seleccionar plan
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
