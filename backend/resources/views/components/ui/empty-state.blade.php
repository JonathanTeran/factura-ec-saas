@props([
    'title' => 'Sin datos',
    'description' => null,
    'icon' => null,
    'action' => null,
    'actionUrl' => null,
    'actionIcon' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-6 text-center']) }}>
    @if($icon)
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
            {!! $icon !!}
        </div>
    @else
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
        </div>
    @endif

    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>

    @if($description)
        <p class="mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif

    @if($action && $actionUrl)
        <div class="mt-6">
            <x-ui.button :href="$actionUrl" variant="primary">
                @if($actionIcon)
                    {!! $actionIcon !!}
                @else
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                @endif
                {{ $action }}
            </x-ui.button>
        </div>
    @endif

    {{ $slot }}
</div>
