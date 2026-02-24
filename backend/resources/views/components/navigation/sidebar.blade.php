@props(['mobile' => false])

<div class="flex h-full flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950">
    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center px-5 border-b border-white/[0.06]">
        <a href="{{ route('panel.dashboard') }}" class="flex items-center gap-3 group">
            <img src="{{ asset('images/app_icon_transparent.png') }}" alt="AmePhia" class="h-9 w-9 rounded-xl object-contain">
            <img x-show="!sidebarCollapsed || {{ $mobile ? 'true' : 'false' }}"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 src="{{ asset('images/logo_dark_bg_v2.png') }}" alt="AmePhia Facturacion" class="h-7 object-contain">
        </a>
    </div>

    {{-- Company selector --}}
    @if(auth()->user()->tenant?->companies->count() > 0)
    <div class="px-3 py-3 border-b border-white/[0.06]" x-show="!sidebarCollapsed || {{ $mobile ? 'true' : 'false' }}">
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 rounded-xl bg-white/[0.04] px-3 py-2.5 hover:bg-white/[0.08] transition-all duration-200 group">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/[0.08] text-slate-300 text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->currentCompany?->trade_name ?? 'E', 0, 2)) }}
                </div>
                <div class="flex-1 text-left min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->currentCompany?->trade_name ?? 'Seleccionar empresa' }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->currentCompany?->ruc ?? '' }}</p>
                </div>
                <svg class="h-4 w-4 text-slate-500 shrink-0 group-hover:text-slate-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
            </button>

            <div x-show="open" x-cloak
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute left-0 right-0 mt-1.5 rounded-xl bg-slate-800 shadow-xl ring-1 ring-white/[0.08] z-50 overflow-hidden p-1">
                @foreach(auth()->user()->tenant->companies as $company)
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/[0.06] transition-colors {{ auth()->user()->current_company_id === $company->id ? 'bg-white/[0.06]' : '' }}">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ auth()->user()->current_company_id === $company->id ? 'bg-primary-600 text-white' : 'bg-white/[0.06] text-slate-400' }} text-xs font-bold transition-colors">
                        {{ strtoupper(substr($company->trade_name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $company->trade_name }}</p>
                        <p class="text-[11px] text-slate-500">{{ $company->ruc }}</p>
                    </div>
                    @if(auth()->user()->current_company_id === $company->id)
                    <svg class="h-4 w-4 text-primary-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        {{-- Main menu --}}
        <div class="space-y-0.5">
            <x-navigation.sidebar-link
                href="{{ route('panel.dashboard') }}"
                icon="home"
                :active="request()->routeIs('panel.dashboard')"
                :collapsed="!$mobile">
                Dashboard
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.documents.index') }}"
                icon="document"
                :active="request()->routeIs('panel.documents.*')"
                :collapsed="!$mobile"
                badge="{{ auth()->user()->tenant?->documents_this_month ?? 0 }}">
                Documentos
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.customers.index') }}"
                icon="users"
                :active="request()->routeIs('panel.customers.*')"
                :collapsed="!$mobile">
                Clientes
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.products.index') }}"
                icon="cube"
                :active="request()->routeIs('panel.products.*')"
                :collapsed="!$mobile">
                Productos
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.categories.index') }}"
                icon="tag"
                :active="request()->routeIs('panel.categories.*')"
                :collapsed="!$mobile">
                Categorías
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.inventory.index') }}"
                icon="archive-box"
                :active="request()->routeIs('panel.inventory.*')"
                :collapsed="!$mobile">
                Inventario
            </x-navigation.sidebar-link>
        </div>

        {{-- Separator --}}
        <div class="pt-5 pb-2" x-show="!sidebarCollapsed || {{ $mobile ? 'true' : 'false' }}">
            <p class="section-label">Crear</p>
        </div>
        <div class="pt-2" x-show="sidebarCollapsed && {{ $mobile ? 'false' : 'true' }}">
            <div class="mx-3 h-px bg-white/[0.06]"></div>
        </div>

        {{-- Quick actions --}}
        <div class="space-y-0.5">
            <x-navigation.sidebar-link
                href="{{ route('panel.invoices.create') }}"
                icon="plus-circle"
                :active="request()->routeIs('panel.invoices.create')"
                :collapsed="!$mobile"
                variant="highlight">
                Nueva Factura
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.credit-notes.create') }}"
                icon="receipt-refund"
                :active="request()->routeIs('panel.credit-notes.create')"
                :collapsed="!$mobile">
                Nota de Crédito
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.debit-notes.create') }}"
                icon="receipt"
                :active="request()->routeIs('panel.debit-notes.create')"
                :collapsed="!$mobile">
                Nota de Débito
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.retention.create') }}"
                icon="document-check"
                :active="request()->routeIs('panel.retention.create')"
                :collapsed="!$mobile">
                Retención
            </x-navigation.sidebar-link>

            <x-navigation.sidebar-link
                href="{{ route('panel.guides.create') }}"
                icon="truck"
                :active="request()->routeIs('panel.guides.create')"
                :collapsed="!$mobile">
                Guía de Remisión
            </x-navigation.sidebar-link>
        </div>

        {{-- Separator --}}
        <div class="pt-5 pb-2" x-show="!sidebarCollapsed || {{ $mobile ? 'true' : 'false' }}">
            <p class="section-label">Análisis</p>
        </div>
        <div class="pt-2" x-show="sidebarCollapsed && {{ $mobile ? 'false' : 'true' }}">
            <div class="mx-3 h-px bg-white/[0.06]"></div>
        </div>

        <div class="space-y-0.5">
            <x-navigation.sidebar-link
                href="{{ route('panel.reports.index') }}"
                icon="chart-bar"
                :active="request()->routeIs('panel.reports.*')"
                :collapsed="!$mobile">
                Reportes
            </x-navigation.sidebar-link>
        </div>
    </nav>

    {{-- Plan usage --}}
    <div class="px-3 py-3 border-t border-white/[0.06]" x-show="!sidebarCollapsed || {{ $mobile ? 'true' : 'false' }}">
        @php
            $tenant = auth()->user()->tenant;
            $usage = $tenant ? ($tenant->documents_this_month / max($tenant->max_documents_per_month, 1)) * 100 : 0;
            $usageColor = $usage >= 90 ? 'bg-rose-500' : ($usage >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
        @endphp
        <div class="rounded-xl bg-white/[0.04] p-3.5">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-medium text-slate-400">Documentos</span>
                <span class="text-[11px] font-bold text-white tabular-nums">{{ $tenant?->documents_this_month ?? 0 }}<span class="text-slate-500">/{{ $tenant?->max_documents_per_month ?? 0 }}</span></span>
            </div>
            <div class="progress-bar !h-1 !bg-white/[0.08]">
                <div class="progress-fill {{ $usageColor }}" style="width: {{ min($usage, 100) }}%"></div>
            </div>
            @if($usage >= 80)
            <a href="{{ route('panel.settings.billing') }}"
               class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-primary-600 hover:bg-primary-500 px-3 py-2 text-xs font-semibold text-white shadow-sm shadow-primary-500/20 transition-all duration-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                Ampliar plan
            </a>
            @endif
        </div>
    </div>

    {{-- Settings link --}}
    <div class="px-3 pb-3">
        <x-navigation.sidebar-link
            href="{{ route('panel.settings.index') }}"
            icon="cog"
            :active="request()->routeIs('panel.settings*')"
            :collapsed="!$mobile">
            Configuración
        </x-navigation.sidebar-link>
    </div>
</div>
