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
                Programa de Referidos
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Invita a otros negocios y gana comisiones por cada referido que se suscriba
            </p>
        </div>
    </div>

    {{-- Referral Link Card --}}
    <div class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white shadow-xl isolate">
        {{-- Decorative elements --}}
        <div class="absolute -right-20 -top-20 -z-10 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 -z-10 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"></div>

        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500/20 text-teal-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-slate-400">Tu enlace de referido</p>
                </div>
                <div class="mt-3">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Codigo</p>
                    <p class="mt-1 text-2xl font-bold tracking-widest text-teal-400">{{ $referralCode }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:items-end">
                <div class="flex flex-wrap gap-3">
                    <button wire:click="copyLink"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                        </svg>
                        Copiar enlace
                    </button>
                    <button wire:click="shareWhatsApp"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Compartir por WhatsApp
                    </button>
                </div>
                <p class="text-xs text-slate-500">
                    {{ $referralLink }}
                </p>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Referrals --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total referidos</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $totalReferrals }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $activeReferrals }} con suscripcion activa</p>
        </div>

        {{-- Total Earned --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total ganado</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">${{ number_format($totalEarned, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">${{ number_format($totalPaid, 2) }} pagado</p>
        </div>

        {{-- Pending Amount --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pendiente de pago</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">${{ number_format($pendingAmount, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Por aprobar o pagar</p>
        </div>

        {{-- Commission Rate --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tasa de comision</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">10%</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Por cada pago del referido</p>
        </div>
    </div>

    {{-- How it works --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Como funciona</h3>
        <div class="grid gap-6 sm:grid-cols-3">
            <div class="flex gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 font-bold dark:bg-teal-900/30 dark:text-teal-400">
                    1
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Comparte tu enlace</h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Envia tu enlace de referido a otros negocios que necesiten facturacion electronica.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 font-bold dark:bg-teal-900/30 dark:text-teal-400">
                    2
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Se registran y suscriben</h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuando se registren con tu enlace y contraten un plan, se vinculan como tu referido.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 font-bold dark:bg-teal-900/30 dark:text-teal-400">
                    3
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Ganas comisiones</h4>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Recibes el 10% de cada pago que realice tu referido mientras mantenga su suscripcion.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Commission History --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Historial de comisiones</h3>

        @if ($commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Fecha</th>
                            <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Referido</th>
                            <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Tasa</th>
                            <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Comision</th>
                            <th class="pb-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400">Estado</th>
                            <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Pagado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach ($commissions as $commission)
                            <tr>
                                <td class="py-4 text-sm text-slate-900 dark:text-white">
                                    {{ $commission->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-4 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $commission->referredTenant->name ?? 'N/A' }}
                                </td>
                                <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">
                                    {{ number_format($commission->commission_rate, 0) }}%
                                </td>
                                <td class="py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    ${{ number_format($commission->commission_amount, 2) }}
                                </td>
                                <td class="py-4 text-center">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$commission->status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                        {{ $commission->getStatusLabel() }}
                                    </span>
                                </td>
                                <td class="py-4 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $commission->paid_at?->format('d/m/Y') ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $commissions->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">Sin comisiones aun</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Comparte tu enlace de referido para comenzar a ganar comisiones.
                </p>
                <div class="mt-6">
                    <button wire:click="copyLink"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:bg-teal-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                        Copiar enlace de referido
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    $wire.on('copy-to-clipboard', ({ text }) => {
        navigator.clipboard.writeText(text).catch(() => {
            // Fallback for older browsers
            const el = document.createElement('textarea');
            el.value = text;
            el.setAttribute('readonly', '');
            el.style.position = 'absolute';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        });
    });

    $wire.on('open-url', ({ url }) => {
        window.open(url, '_blank');
    });
</script>
@endscript
