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
                Facturación
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Gestiona tu suscripción y métodos de pago
            </p>
        </div>
    </div>

    {{-- Current Subscription --}}
    @if ($currentPlan)
        <div class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white shadow-xl isolate">
            {{-- Decorative elements --}}
            <div class="absolute -right-20 -top-20 -z-10 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 -z-10 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"></div>

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500/20 text-teal-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                        </span>
                        <p class="text-sm font-medium text-slate-400">Tu plan actual</p>
                    </div>
                    <h2 class="mt-2 text-3xl font-bold text-white">{{ $currentPlan->name }}</h2>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($pendingSubscription)
                            <span
                                class="inline-flex items-center rounded-full bg-amber-400/10 px-2.5 py-0.5 text-xs font-medium text-amber-400 ring-1 ring-inset ring-amber-400/20">Pendiente de revisión</span>
                            <span class="text-sm text-slate-400">Tu comprobante está siendo revisado</span>
                        @elseif ($currentSubscription)
                            @if ($currentSubscription->isCanceled())
                                <span
                                    class="inline-flex items-center rounded-full bg-red-400/10 px-2.5 py-0.5 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-400/20">Cancelado</span>
                                <span class="text-sm text-slate-400">Válido hasta
                                    {{ $currentSubscription->ends_at->format('d/m/Y') }}</span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-400/20">Activo</span>
                                <span class="text-sm text-slate-400">Próxima facturación:
                                    {{ $currentSubscription->next_payment_at?->format('d/m/Y') }}</span>
                            @endif
                        @else
                            <span
                                class="inline-flex items-center rounded-full bg-teal-400/10 px-2.5 py-0.5 text-xs font-medium text-teal-400 ring-1 ring-inset ring-teal-400/20">Sin suscripción</span>
                            <span class="text-sm text-slate-400">Selecciona un plan para suscribirte</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if ($pendingSubscription)
                        {{-- No actions while pending review --}}
                    @elseif ($currentSubscription)
                        @if ($currentSubscription->isCanceled())
                            <button wire:click="resumeSubscription"
                                class="rounded-xl bg-teal-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500">
                                Reactivar suscripción
                            </button>
                        @else
                            <button wire:click="openCancelModal"
                                class="rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-semibold text-slate-300 ring-1 ring-inset ring-slate-700 transition hover:bg-slate-700 hover:text-white">
                                Cancelar
                            </button>
                            <button wire:click="openUpgradeModal"
                                class="rounded-xl bg-teal-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500">
                                Cambiar plan
                            </button>
                        @endif
                    @else
                        <button wire:click="openUpgradeModal"
                            class="rounded-xl bg-teal-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500">
                            Suscribirse
                        </button>
                    @endif
                </div>
            </div>

            {{-- Usage Stats --}}
            @if (count($usageStats) > 0)
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach ($usageStats as $key => $stat)
                        <div class="rounded-xl bg-slate-800/50 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                            <div class="mb-4 flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-400">
                                    {{ $key === 'documents' ? 'Documentos' : ($key === 'users' ? 'Usuarios' : 'Empresas') }}
                                </p>
                                @if ($key === 'documents')
                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                @elseif ($key === 'users')
                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                @else
                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                @endif
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-3xl font-bold tracking-tight text-white">{{ $stat['used'] }}</span>
                                @if (!$stat['unlimited'])
                                    <span class="text-sm font-medium text-slate-500">/ {{ $stat['limit'] }}</span>
                                @else
                                    <span class="text-sm font-medium text-slate-500">/ Ilimitado</span>
                                @endif
                            </div>
                            @if (!$stat['unlimited'])
                                @php
                                    $percentage =
                                        $stat['limit'] > 0 ? min(100, ($stat['used'] / $stat['limit']) * 100) : 0;
                                    $barColor =
                                        $percentage >= 90
                                            ? 'bg-red-500'
                                            : ($percentage >= 75
                                                ? 'bg-amber-500'
                                                : 'bg-teal-500');
                                @endphp
                                <div class="mt-4 flex h-1.5 w-full overflow-hidden rounded-full bg-slate-700">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Pending subscription alert --}}
            @if ($pendingSubscription)
                <div class="mt-6 flex items-start gap-3 rounded-xl bg-amber-500/10 p-4 ring-1 ring-inset ring-amber-500/20">
                    <svg class="h-5 w-5 mt-0.5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-amber-300">
                            Tu suscripción al plan <strong>{{ $pendingSubscription->plan->name }}</strong>
                            ({{ $pendingSubscription->getBillingCycleLabel() }}) está pendiente de revisión.
                        </p>
                        <p class="mt-1 text-xs text-amber-400/70">
                            Enviado el {{ $pendingSubscription->created_at->format('d/m/Y H:i') }} — Revisaremos tu comprobante en un plazo máximo de 24 horas.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- No subscription --}}
        <div class="rounded-2xl bg-slate-100 p-6 text-center dark:bg-slate-800">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
            <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Sin suscripción activa</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selecciona un plan para comenzar a facturar</p>
        </div>
    @endif

    {{-- Plans --}}
    <div>
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Planes disponibles</h3>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
                @php
                    $isActuallyActive = false;
                    if ($currentPlan && $currentPlan->id === $plan->id) {
                        if (
                            $currentSubscription &&
                            ($currentSubscription->isActive() || $currentSubscription->isCanceled())
                        ) {
                            $isActuallyActive = true;
                        } elseif (!$currentSubscription) {
                            // Plan assigned without subscription (manually or on registration)
                            $isActuallyActive = true;
                        }
                    }
                @endphp
                <div
                    class="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10 {{ $isActuallyActive ? 'ring-2 ring-teal-600 dark:ring-teal-500' : '' }}">
                    @if ($plan->is_featured)
                        <div
                            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-teal-600 px-3 py-1 text-xs font-semibold text-white">
                            Popular
                        </div>
                    @endif

                    <div class="text-center">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h4>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>

                        <div class="mt-4">
                            <span
                                class="text-4xl font-bold text-slate-900 dark:text-white">${{ number_format($plan->price_monthly, 2) }}</span>
                            <span class="text-sm text-slate-500">/mes</span>
                        </div>
                        @if ($plan->price_yearly)
                            @php
                                $yearlyBase = $plan->price_monthly * 12;
                                $yearlySavings =
                                    $yearlyBase > 0 ? round((1 - $plan->price_yearly / $yearlyBase) * 100) : null;
                            @endphp
                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                ${{ number_format($plan->price_yearly, 2) }}/año
                                @if (!is_null($yearlySavings))
                                    (ahorra {{ $yearlySavings }}%)
                                @endif
                            </p>
                        @endif
                    </div>

                    <ul class="mt-6 space-y-3">
                        <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            {{ $plan->max_documents_per_month === -1 ? 'Documentos ilimitados' : $plan->max_documents_per_month . ' documentos/mes' }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            {{ $plan->max_users === -1 ? 'Usuarios ilimitados' : $plan->max_users . ' usuarios' }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            {{ $plan->max_companies === -1 ? 'Empresas ilimitadas' : $plan->max_companies . ' empresa' . ($plan->max_companies > 1 ? 's' : '') }}
                        </li>
                        @if ($plan->has_api_access)
                            <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Acceso a API
                            </li>
                        @endif
                        @if ($plan->has_inventory)
                            <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Inventario
                            </li>
                        @endif
                        @if (in_array($plan->support_level, ['priority', 'dedicated']))
                            <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Soporte prioritario
                            </li>
                        @endif
                    </ul>

                    <div class="mt-6">
                        @if ($isActuallyActive)
                            <div
                                class="rounded-xl bg-teal-50 px-4 py-2.5 text-center text-sm font-semibold text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">
                                Plan actual
                            </div>
                        @elseif ($pendingSubscription && $pendingSubscription->plan_id === $plan->id)
                            <div
                                class="rounded-xl bg-amber-50 px-4 py-2.5 text-center text-sm font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                Pendiente de revisión
                            </div>
                        @elseif ($pendingSubscription)
                            <button disabled
                                class="w-full rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed dark:bg-slate-700 dark:text-slate-500">
                                Seleccionar plan
                            </button>
                        @else
                            <button wire:click="openUpgradeModal({{ $plan->id }})"
                                class="w-full rounded-xl {{ $plan->is_featured ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/25 hover:bg-teal-700' : 'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600' }} px-4 py-2.5 text-sm font-semibold transition">
                                Seleccionar plan
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Payment History --}}
    @if ($paymentHistory->count() > 0)
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Historial de pagos</h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Fecha
                            </th>
                            <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">
                                Descripción</th>
                            <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Monto
                            </th>
                            <th class="pb-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400">Estado
                            </th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach ($paymentHistory as $payment)
                            <tr>
                                <td class="py-4 text-sm text-slate-900 dark:text-white">
                                    {{ $payment->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-4 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $payment->description ?? 'Pago de suscripción' }}
                                </td>
                                <td class="py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    ${{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="py-4 text-center">
                                    @php
                                        $statusColors = [
                                            'completed' =>
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'pending' =>
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            'refunded' =>
                                                'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                        ];
                                        $statusLabels = [
                                            'completed' => 'Completado',
                                            'pending' => 'Pendiente',
                                            'failed' => 'Fallido',
                                            'refunded' => 'Reembolsado',
                                        ];
                                    @endphp
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$payment->status->value] ?? $statusColors['pending'] }}">
                                        {{ $statusLabels[$payment->status->value] ?? $payment->status->value }}
                                    </span>
                                </td>
                                <td class="py-4">
                                    @if ($payment->status->value === 'completed')
                                        <button wire:click="downloadInvoice({{ $payment->id }})"
                                            class="text-sm font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400">
                                            Descargar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Upgrade Modal --}}
    @if ($showUpgradeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeUpgradeModal"
                    class="fixed inset-0 bg-slate-500/75 transition-opacity dark:bg-slate-900/75"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div
                    class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-lg sm:align-middle">
                    <form wire:submit="subscribe">
                        <div class="px-6 pb-4 pt-5">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ $currentSubscription ? 'Cambiar plan' : 'Suscribirse' }}
                            </h3>

                            {{-- Error summary --}}
                            @if ($errors->any())
                                <div class="mt-3 rounded-xl bg-red-50 p-4 dark:bg-red-900/20 ring-1 ring-inset ring-red-200 dark:ring-red-800">
                                    <div class="flex items-start gap-3">
                                        <svg class="h-5 w-5 mt-0.5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-semibold text-red-800 dark:text-red-300">Por favor corrige los siguientes errores:</p>
                                            <ul class="mt-1.5 list-disc list-inside space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li class="text-sm text-red-700 dark:text-red-400">{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 space-y-4">
                                {{-- Plan selection --}}
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Plan <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="selectedPlanId"
                                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                                        <option value="">Seleccionar plan...</option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }} -
                                                ${{ number_format($plan->price_monthly, 2) }}/mes</option>
                                        @endforeach
                                    </select>
                                    @error('selectedPlanId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Billing cycle --}}
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Ciclo
                                        de facturación</label>
                                    <div class="flex gap-3">
                                        <label class="flex-1">
                                            <input type="radio" wire:model.live="billingCycle" value="monthly"
                                                class="peer sr-only" @checked($billingCycle === 'monthly')>
                                            <div
                                                class="cursor-pointer rounded-xl border-2 border-slate-200 px-4 py-3 text-center text-sm font-medium text-slate-600 transition peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700 dark:border-slate-700 dark:text-slate-400 dark:peer-checked:border-teal-500 dark:peer-checked:bg-teal-900/20 dark:peer-checked:text-teal-400">
                                                Mensual
                                            </div>
                                        </label>
                                        <label class="flex-1">
                                            <input type="radio" wire:model.live="billingCycle" value="yearly"
                                                class="peer sr-only" @checked($billingCycle === 'yearly')>
                                            <div
                                                class="cursor-pointer rounded-xl border-2 border-slate-200 px-4 py-3 text-center text-sm font-medium text-slate-600 transition peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700 dark:border-slate-700 dark:text-slate-400 dark:peer-checked:border-teal-500 dark:peer-checked:bg-teal-900/20 dark:peer-checked:text-teal-400">
                                                Anual
                                                <span class="ml-1 text-xs text-emerald-600">-20%</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- Coupon --}}
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Cupón
                                        de descuento</label>
                                    <div class="flex gap-2">
                                        <input wire:model="couponCode" type="text" placeholder="Ingresa tu cupón"
                                            class="block flex-1 rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                        <button wire:click="applyCoupon" type="button"
                                            class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                            Aplicar
                                        </button>
                                    </div>
                                    @error('couponCode')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @if ($couponInfo)
                                        <div
                                            class="mt-2 flex items-center justify-between rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/20">
                                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                                {{ $couponInfo['name'] }}: {{ $couponInfo['discount_label'] }}
                                            </span>
                                            <button wire:click="removeCoupon" type="button"
                                                class="text-emerald-600 hover:text-emerald-700">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- Payment Method Selector --}}
                                <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
                                    <label
                                        class="mb-3 block text-sm font-semibold text-slate-900 dark:text-white">Método
                                        de pago</label>
                                    <div class="flex gap-3">
                                        @foreach ($enabledPaymentMethods as $method)
                                            <label class="flex-1">
                                                <input type="radio" wire:model.live="paymentMethod"
                                                    value="{{ $method->code }}" class="peer sr-only"
                                                    @checked($paymentMethod === $method->code)>
                                                <div
                                                    class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-slate-200 px-4 py-3 transition peer-checked:border-teal-600 peer-checked:bg-teal-50 dark:border-slate-700 dark:peer-checked:border-teal-500 dark:peer-checked:bg-teal-900/20">
                                                    @if ($method->code === 'transfer')
                                                        <svg class="h-5 w-5 text-slate-500" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-slate-500" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                                        </svg>
                                                    @endif
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                                                            {{ $method->name }}</p>
                                                        @if ($method->description)
                                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                                {{ $method->description }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Bank Transfer Panel --}}
                                @if ($paymentMethod === 'transfer')
                                    <div class="space-y-4 rounded-xl bg-amber-50 p-4 dark:bg-amber-900/10">
                                        {{-- Bank accounts info --}}
                                        <div>
                                            <h5 class="mb-2 text-sm font-semibold text-slate-900 dark:text-white">Datos
                                                bancarios para transferencia</h5>
                                            @foreach ($bankAccounts as $account)
                                                <div
                                                    class="mb-2 rounded-lg bg-white p-3 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                                                    <div class="grid grid-cols-2 gap-1 text-sm">
                                                        <span class="text-slate-500 dark:text-slate-400">Banco:</span>
                                                        <span
                                                            class="font-medium text-slate-900 dark:text-white">{{ $account->bank_name }}</span>
                                                        <span class="text-slate-500 dark:text-slate-400">Tipo:</span>
                                                        <span
                                                            class="font-medium text-slate-900 dark:text-white">{{ $account->account_type }}</span>
                                                        <span class="text-slate-500 dark:text-slate-400">Número:</span>
                                                        <span
                                                            class="font-medium text-slate-900 dark:text-white">{{ $account->account_number }}</span>
                                                        <span
                                                            class="text-slate-500 dark:text-slate-400">Titular:</span>
                                                        <span
                                                            class="font-medium text-slate-900 dark:text-white">{{ $account->holder_name }}</span>
                                                        <span class="text-slate-500 dark:text-slate-400">RUC/CI:</span>
                                                        <span
                                                            class="font-medium text-slate-900 dark:text-white">{{ $account->holder_identification }}</span>
                                                    </div>
                                                    @if ($account->instructions)
                                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $account->instructions }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Transfer reference --}}
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                                Referencia / N° de comprobante <span class="text-red-500">*</span>
                                            </label>
                                            <input wire:model="transferReference" type="text"
                                                placeholder="Ej: 123456789"
                                                class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                            @error('transferReference')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        {{-- Receipt upload --}}
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                                Comprobante de transferencia <span class="text-red-500">*</span>
                                            </label>
                                            <div class="flex items-center justify-center w-full">
                                                <label
                                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer border-slate-300 bg-white hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:hover:bg-slate-800">
                                                    @if ($transferReceipt)
                                                        <div
                                                            class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <svg class="w-8 h-8 mb-2 text-emerald-500" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <p class="text-sm text-emerald-600 font-medium">
                                                                {{ $transferReceipt->getClientOriginalName() }}</p>
                                                            <p class="text-xs text-slate-500 mt-1">Click para cambiar
                                                            </p>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <svg class="w-8 h-8 mb-2 text-slate-400" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                                            </svg>
                                                            <p class="text-sm text-slate-500"><span
                                                                    class="font-semibold text-teal-600">Subir
                                                                    imagen</span> del comprobante</p>
                                                            <p class="text-xs text-slate-400 mt-1">PNG, JPG hasta 5MB
                                                            </p>
                                                        </div>
                                                    @endif
                                                    <input wire:model="transferReceipt" type="file" class="hidden"
                                                        accept="image/*" />
                                                </label>
                                            </div>
                                            <div wire:loading wire:target="transferReceipt" class="mt-2">
                                                <div class="flex items-center gap-2 text-sm text-teal-600">
                                                    <svg class="h-4 w-4 animate-spin" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12"
                                                            r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Subiendo comprobante...
                                                </div>
                                            </div>
                                            @error('transferReceipt')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        {{-- Info note --}}
                                        <div
                                            class="flex items-start gap-2 rounded-lg bg-amber-100 p-3 dark:bg-amber-900/20">
                                            <svg class="h-5 w-5 mt-0.5 shrink-0 text-amber-600" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                            <p class="text-sm text-amber-800 dark:text-amber-300">
                                                Tu pago será revisado y aprobado en un plazo máximo de <strong>24
                                                    horas</strong>. Recibirás una notificación cuando tu suscripción sea
                                                activada.
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Payment Method Instructions Panel --}}
                                @if ($paymentMethod !== 'transfer')
                                    @php
                                        $currentMethodSetting = $enabledPaymentMethods->firstWhere(
                                            'code',
                                            $paymentMethod,
                                        );
                                    @endphp
                                    @if ($currentMethodSetting && $currentMethodSetting->instructions)
                                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                                            <div
                                                class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                </svg>
                                                <p>{{ $currentMethodSetting->instructions }}</p>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                {{-- Billing info --}}
                                <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
                                    <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Datos de
                                        facturación</h4>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <input wire:model="billingName" type="text"
                                                placeholder="Nombre completo *"
                                                class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset {{ $errors->has('billingName') ? 'ring-red-500' : 'ring-slate-200' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                            @error('billingName')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <input wire:model="billingEmail" type="email" placeholder="Email *"
                                                class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset {{ $errors->has('billingEmail') ? 'ring-red-500' : 'ring-slate-200' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                            @error('billingEmail')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <input wire:model="billingIdentification" type="text"
                                                placeholder="RUC o Cédula *"
                                                class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset {{ $errors->has('billingIdentification') ? 'ring-red-500' : 'ring-slate-200' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                            @error('billingIdentification')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Total --}}
                                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600 dark:text-slate-400">Total a pagar:</span>
                                        <span
                                            class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format($selectedPlanPrice, 2) }}</span>
                                    </div>
                                </div>

                                @error('payment')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4 dark:bg-slate-900/50">
                            <button wire:click="closeUpgradeModal" type="button"
                                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                Cancelar
                            </button>
                            @php
                                $selectedMethod = $enabledPaymentMethods->firstWhere('code', $paymentMethod);
                                $isDisabled =
                                    $selectedMethod &&
                                    $selectedMethod->requires_gateway &&
                                    $selectedMethod->instructions;
                            @endphp
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                {{ $isDisabled ? 'disabled' : '' }}
                                wire:loading.attr="disabled" wire:target="transferReceipt, subscribe">
                                <svg wire:loading wire:target="subscribe" class="h-4 w-4 animate-spin" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                @if ($paymentMethod === 'transfer')
                                    <span wire:loading.remove wire:target="subscribe">Enviar comprobante</span>
                                @else
                                    <span wire:loading.remove wire:target="subscribe">Suscribirse</span>
                                @endif
                                <span wire:loading wire:target="subscribe">Procesando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancel Modal --}}
    @if ($showCancelModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeCancelModal"
                    class="fixed inset-0 bg-slate-500/75 transition-opacity dark:bg-slate-900/75"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div
                    class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-md sm:align-middle">
                    <div class="px-6 pb-4 pt-5">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-center text-lg font-semibold text-slate-900 dark:text-white">
                            Cancelar suscripción
                        </h3>
                        <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">
                            Tu suscripción permanecerá activa hasta el final del período actual. Después de eso,
                            perderás acceso a las funciones premium.
                        </p>

                        <div class="mt-4">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Motivo de cancelación (opcional)
                            </label>
                            <textarea wire:model="cancelReason" rows="3" placeholder="Cuéntanos por qué cancelas..."
                                class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-teal-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500"></textarea>
                        </div>

                        @error('cancel')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4 dark:bg-slate-900/50">
                        <button wire:click="closeCancelModal" type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                            Mantener suscripción
                        </button>
                        <button wire:click="cancelSubscription" type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-red-700">
                            <svg wire:loading wire:target="cancelSubscription" class="h-4 w-4 animate-spin"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="cancelSubscription">Confirmar cancelación</span>
                            <span wire:loading wire:target="cancelSubscription">Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
