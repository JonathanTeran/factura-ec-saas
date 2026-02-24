@props([
    'title',
    'value',
    'change' => null,
    'changeType' => 'neutral', // positive, negative, neutral
    'icon' => null,
    'iconBg' => 'bg-blue-500',
    'href' => null,
])

@php
$changeColors = [
    'positive' => 'text-emerald-600 dark:text-emerald-400',
    'negative' => 'text-rose-600 dark:text-rose-400',
    'neutral' => 'text-slate-500 dark:text-slate-400',
];
$changeIcons = [
    'positive' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />',
    'negative' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />',
    'neutral' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15" />',
];
@endphp

<x-ui.card hover class="relative overflow-hidden">
    {{-- Decorative gradient --}}
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full {{ $iconBg }} opacity-10 blur-2xl"></div>

    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>

            @if($change)
            <div class="mt-2 flex items-center gap-1.5">
                <svg class="h-4 w-4 {{ $changeColors[$changeType] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    {!! $changeIcons[$changeType] !!}
                </svg>
                <span class="text-sm font-medium {{ $changeColors[$changeType] }}">{{ $change }}</span>
                <span class="text-sm text-slate-400 dark:text-slate-500">vs mes anterior</span>
            </div>
            @endif
        </div>

        @if($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $iconBg }} text-white shadow-lg">
            {!! $icon !!}
        </div>
        @endif
    </div>

    @if($href)
    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
        <a href="{{ $href }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1 group">
            Ver detalles
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </a>
    </div>
    @endif
</x-ui.card>
