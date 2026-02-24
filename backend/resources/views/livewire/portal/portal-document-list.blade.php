<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
            Mis Documentos
        </h1>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Consulta y descarga tus documentos electronicos
        </p>
    </div>

    {{-- Filtros --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Buscar --}}
            <div>
                <label for="search" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Buscar</label>
                <input
                    wire:model.live.debounce.300ms="search"
                    id="search"
                    type="text"
                    placeholder="No. documento o clave acceso..."
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>

            {{-- Tipo documento --}}
            <div>
                <label for="type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select
                    wire:model.live="type"
                    id="type"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Todos</option>
                    @foreach($documentTypes as $docType)
                    <option value="{{ $docType->value }}">{{ $docType->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Fecha desde --}}
            <div>
                <label for="dateFrom" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Desde</label>
                <input
                    wire:model.live="dateFrom"
                    id="dateFrom"
                    type="date"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>

            {{-- Fecha hasta --}}
            <div>
                <label for="dateTo" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Hasta</label>
                <input
                    wire:model.live="dateTo"
                    id="dateTo"
                    type="date"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>

            {{-- Limpiar --}}
            <div class="flex items-end">
                <button
                    wire:click="clearFilters"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th wire:click="sortBy('document_type')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Tipo
                            @if($sortField === 'document_type')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th wire:click="sortBy('sequential')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Numero
                            @if($sortField === 'sequential')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Empresa
                        </th>
                        <th wire:click="sortBy('issue_date')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Fecha
                            @if($sortField === 'issue_date')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th wire:click="sortBy('total')" class="cursor-pointer px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Total
                            @if($sortField === 'total')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Descargar
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($documents as $doc)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $doc->document_type->shortLabel() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('portal.documents.show', $doc->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                {{ $doc->getDocumentNumber() }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $doc->company->business_name ?? '-' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $doc->issue_date->format('d/m/Y') }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($doc->total, 2) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($doc->ride_pdf_path)
                                <a href="{{ route('portal.documents.ride', $doc->id) }}"
                                   class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                   title="Descargar PDF">
                                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    PDF
                                </a>
                                @endif
                                @if($doc->xml_authorized_path)
                                <a href="{{ route('portal.documents.xml', $doc->id) }}"
                                   class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                   title="Descargar XML">
                                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                                    </svg>
                                    XML
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron documentos.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginacion --}}
        @if($documents->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $documents->links() }}
        </div>
        @endif
    </div>
</div>
