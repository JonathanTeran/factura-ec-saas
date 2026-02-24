<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Proveedores
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Directorio de proveedores registrados
            </p>
        </div>
        <a href="{{ route('panel.purchases.create') }}" class="btn-primary">
            Nueva compra
        </a>
    </div>

    {{-- Search --}}
    <div class="card p-4">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre, RUC o email..."
            class="input w-full" />
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('business_name')">Razon Social</th>
                        <th class="px-4 py-3 font-medium">Identificacion</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Telefono</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('total_purchased')">Total comprado</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-medium">{{ $supplier->business_name }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $supplier->identification }}</td>
                            <td class="px-4 py-3">{{ $supplier->email ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $supplier->phone ?? '-' }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold">${{ number_format($supplier->total_purchased, 2) }}</td>
                            <td class="px-4 py-3">
                                <button wire:click="toggleActive({{ $supplier->id }})"
                                    class="badge {{ $supplier->is_active ? 'badge-green' : 'badge-gray' }} cursor-pointer">
                                    {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="deleteSupplier({{ $supplier->id }})"
                                    wire:confirm="Esta seguro de eliminar este proveedor?"
                                    class="btn-icon-sm text-red-500 hover:text-red-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No hay proveedores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>
