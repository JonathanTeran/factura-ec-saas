@props([
    'padding' => true,
    'hover' => false,
])

<div {{ $attributes->merge([
    'class' => 'rounded-xl bg-white ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-slate-800 ' .
               ($hover ? 'transition-shadow hover:shadow-lg hover:ring-slate-900/10 dark:hover:ring-slate-700' : '') .
               ($padding ? ' p-6' : '')
]) }}>
    {{ $slot }}
</div>
