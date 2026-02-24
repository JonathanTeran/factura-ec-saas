<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Centros de Costo
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Estructura jerarquica de centros de costo
            </p>
        </div>
        <button wire:click="openForm" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo centro de costo
        </button>
    </div>

    {{-- Inline Form Modal --}}
    @if($showForm)
        <div class="card p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">
                {{ $editingId ? 'Editar Centro de Costo' : 'Nuevo Centro de Costo' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="form-label">Codigo</label>
                        <input wire:model="code" type="text" class="input w-full" placeholder="CC-001">
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nombre</label>
                        <input wire:model="name" type="text" class="input w-full" placeholder="Administracion">
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Centro padre</label>
                        <select wire:model="parent_id" class="input w-full">
                            <option value="">-- Raiz --</option>
                            @foreach($allCostCenters as $cc)
                                @if($cc->id !== $editingId)
                                    <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                            Activo
                        </label>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-sm">
                        {{ $editingId ? 'Actualizar' : 'Crear' }}
                    </button>
                    <button type="button" wire:click="closeForm" class="btn-ghost text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Tree View --}}
    <div class="card overflow-hidden">
        @if($costCenters->isEmpty())
            <div class="p-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">No hay centros de costo</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea tu primer centro de costo para comenzar.</p>
            </div>
        @else
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($costCenters as $center)
                    {{-- Level 1 --}}
                    <div class="group flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                            <div>
                                <span class="font-mono text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $center->code }}</span>
                                <span class="ml-2 text-sm font-medium text-slate-900 dark:text-white">{{ $center->name }}</span>
                            </div>
                            <span class="badge badge-{{ $center->is_active ? 'green' : 'gray' }}">
                                {{ $center->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            <button wire:click="openForm({{ $center->id }})" class="btn-icon-sm" title="Editar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            <button wire:click="toggleActive({{ $center->id }})" class="btn-icon-sm" title="{{ $center->is_active ? 'Desactivar' : 'Activar' }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                                </svg>
                            </button>
                            <button wire:click="delete({{ $center->id }})"
                                    wire:confirm="Eliminar este centro de costo?"
                                    class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Level 2 children --}}
                    @foreach($center->children as $child)
                        <div class="group flex items-center justify-between px-5 py-2.5 pl-12 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <div class="flex items-center gap-3">
                                <svg class="h-4 w-4 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15" />
                                </svg>
                                <div>
                                    <span class="font-mono text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $child->code }}</span>
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ $child->name }}</span>
                                </div>
                                @if(!$child->is_active)
                                    <span class="badge badge-gray">Inactivo</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <button wire:click="openForm({{ $child->id }})" class="btn-icon-sm" title="Editar">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $child->id }})"
                                        wire:confirm="Eliminar este centro de costo?"
                                        class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Level 3 children --}}
                        @foreach($child->children as $grandchild)
                            <div class="group flex items-center justify-between px-5 py-2 pl-20 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <div class="flex items-center gap-3">
                                    <svg class="h-3 w-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15" />
                                    </svg>
                                    <div>
                                        <span class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $grandchild->code }}</span>
                                        <span class="ml-2 text-xs text-slate-600 dark:text-slate-400">{{ $grandchild->name }}</span>
                                    </div>
                                    @if(!$grandchild->is_active)
                                        <span class="badge badge-gray">Inactivo</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button wire:click="openForm({{ $grandchild->id }})" class="btn-icon-sm" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $grandchild->id }})"
                                            wire:confirm="Eliminar este centro de costo?"
                                            class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</div>
