<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Formularios Tributarios
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Hub de formularios y anexos del SRI
            </p>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-40">
                <label class="form-label">Ano</label>
                <select wire:model.live="selectedYear" class="input w-full">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-48">
                <label class="form-label">Mes</label>
                <select wire:model.live="selectedMonth" class="input w-full">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Monthly Forms --}}
    <div>
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Formularios Mensuales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($formCards as $card)
                @if($card['frequency'] === 'monthly')
                    <div class="card p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg
                                    {{ match($card['status']) {
                                        'generated' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400',
                                        'submitted' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
                                        default => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                    } }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $card['label'] }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $months[$selectedMonth] }} {{ $selectedYear }}
                                    </p>
                                </div>
                            </div>
                            <span class="badge badge-{{ match($card['status']) {
                                'generated' => 'green',
                                'submitted' => 'blue',
                                default => 'gray',
                            } }}">
                                {{ match($card['status']) {
                                    'generated' => 'Generado',
                                    'submitted' => 'Presentado',
                                    default => 'Pendiente',
                                } }}
                            </span>
                        </div>
                        @if($card['generated_at'])
                            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                                Generado: {{ $card['generated_at']->format('d/m/Y H:i') }}
                            </p>
                        @endif
                        <div class="mt-4">
                            @if($card['type'] === 'ats')
                                <a href="{{ route('panel.accounting.ats-generate') }}?year={{ $selectedYear }}&month={{ $selectedMonth }}"
                                   class="btn-primary w-full text-center text-sm" wire:navigate>
                                    Generar ATS
                                </a>
                            @else
                                <a href="{{ route('panel.accounting.tax-form-generate', $card['type']) }}?year={{ $selectedYear }}&month={{ $selectedMonth }}"
                                   class="btn-primary w-full text-center text-sm" wire:navigate>
                                    Generar formulario
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Annual Forms --}}
    <div>
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Formularios Anuales</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($formCards as $card)
                @if($card['frequency'] === 'annual')
                    <div class="card p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg
                                    {{ match($card['status']) {
                                        'generated' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400',
                                        'submitted' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
                                        default => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                    } }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $card['label'] }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        Ano fiscal {{ $selectedYear }}
                                    </p>
                                </div>
                            </div>
                            <span class="badge badge-{{ match($card['status']) {
                                'generated' => 'green',
                                'submitted' => 'blue',
                                default => 'gray',
                            } }}">
                                {{ match($card['status']) {
                                    'generated' => 'Generado',
                                    'submitted' => 'Presentado',
                                    default => 'Pendiente',
                                } }}
                            </span>
                        </div>
                        @if($card['generated_at'])
                            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                                Generado: {{ $card['generated_at']->format('d/m/Y H:i') }}
                            </p>
                        @endif
                        <div class="mt-4">
                            <a href="{{ route('panel.accounting.tax-form-generate', $card['type']) }}?year={{ $selectedYear }}"
                               class="btn-secondary w-full text-center text-sm" wire:navigate>
                                Generar formulario
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
