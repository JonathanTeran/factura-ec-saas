<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
            Hola, {{ $portalSession->customer_name ?? 'Cliente' }}
        </h1>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Bienvenido a tu portal de documentos electronicos
        </p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total documentos</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_documents'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Monto total</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_amount'], 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Documentos {{ date('Y') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['documents_this_year'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Monto {{ date('Y') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['amount_this_year'], 2) }}</p>
        </div>
    </div>

    {{-- Por tipo --}}
    @if($stats['by_type']->count() > 0)
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Documentos por tipo</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($stats['by_type'] as $item)
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $item['count'] }} docs</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($item['total'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Documentos recientes --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Documentos recientes</h2>
            <a href="{{ route('portal.documents.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                Ver todos
            </a>
        </div>

        @if($stats['recent_documents']->count() > 0)
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($stats['recent_documents'] as $doc)
            <a href="{{ route('portal.documents.show', $doc->id) }}" class="flex items-center justify-between px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $doc->document_type->shortLabel() }}
                    </span>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->getDocumentNumber() }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $doc->company->business_name ?? '' }} - {{ $doc->issue_date->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($doc->total, 2) }}</span>
            </a>
            @endforeach
        </div>
        @else
        <div class="px-4 py-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">No hay documentos disponibles.</p>
        </div>
        @endif
    </div>
</div>
