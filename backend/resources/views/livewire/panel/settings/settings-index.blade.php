<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
            Configuración
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Gestiona tu perfil, empresa y facturación
        </p>
    </div>

    {{-- Quick Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Plan actual --}}
        <div class="rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg shadow-blue-500/25">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100">Plan actual</p>
                    <p class="mt-1 text-2xl font-bold">{{ $quickStats['plan_name'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Documentos usados --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Documentos este mes</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ $quickStats['documents_used'] }}
                        @if($quickStats['documents_limit'] > 0)
                            <span class="text-base font-normal text-slate-400">/ {{ $quickStats['documents_limit'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>
            @if($quickStats['documents_limit'] > 0)
                <div class="mt-4">
                    @php
                        $percentage = min(100, ($quickStats['documents_used'] / $quickStats['documents_limit']) * 100);
                        $barColor = $percentage > 80 ? 'bg-red-500' : ($percentage > 60 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Usuarios --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Usuarios</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ $quickStats['users_count'] }}
                        @if($quickStats['users_limit'] > 0)
                            <span class="text-base font-normal text-slate-400">/ {{ $quickStats['users_limit'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Certificado --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Certificado digital</p>
                    @if($quickStats['certificate_expires'])
                        @php
                            $daysLeft = now()->diffInDays($quickStats['certificate_expires'], false);
                        @endphp
                        <p class="mt-1 text-2xl font-bold {{ $daysLeft < 30 ? 'text-red-600' : ($daysLeft < 60 ? 'text-amber-600' : 'text-slate-900 dark:text-white') }}">
                            {{ $daysLeft }} días
                        </p>
                        <p class="text-xs text-slate-500">Vence: {{ $quickStats['certificate_expires']->format('d/m/Y') }}</p>
                    @else
                        <p class="mt-1 text-lg font-medium text-slate-500">No configurado</p>
                    @endif
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $quickStats['certificate_expires'] && now()->diffInDays($quickStats['certificate_expires'], false) < 30 ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Sections --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($sections as $section)
            <a href="{{ route($section['route']) }}" wire:navigate
               class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 transition hover:shadow-md hover:ring-slate-900/10 dark:bg-slate-800 dark:ring-white/10 dark:hover:ring-white/20">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-{{ $section['color'] }}-50 text-{{ $section['color'] }}-600 transition group-hover:scale-110 dark:bg-{{ $section['color'] }}-900/20 dark:text-{{ $section['color'] }}-400">
                    @switch($section['icon'])
                        @case('heroicon-o-user-circle')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            @break
                        @case('heroicon-o-building-office')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                            @break
                        @case('heroicon-o-credit-card')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            @break
                    @endswitch
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $section['title'] }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $section['description'] }}</p>
                <div class="mt-4 flex items-center text-sm font-medium text-{{ $section['color'] }}-600 dark:text-{{ $section['color'] }}-400">
                    <span>Configurar</span>
                    <svg class="ml-1 h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            </a>
        @endforeach
    </div>
</div>
