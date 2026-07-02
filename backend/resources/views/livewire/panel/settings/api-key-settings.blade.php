<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">API Keys</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Genera claves de acceso para integraciones externas con la API REST.
            </p>
        </div>
        <a href="{{ route('panel.settings.index') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
            &larr; Configuración
        </a>
    </div>

    @if (!$hasApiAccess)
        {{-- Plan upgrade required --}}
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Funcionalidad no disponible</h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        El acceso API está disponible en los planes Profesional y Enterprise. Actualiza tu plan para generar API keys.
                    </p>
                    <a href="{{ route('panel.settings.billing') }}" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-yellow-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-yellow-700">
                        Actualizar plan
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Newly created key alert --}}
        @if ($newlyCreatedKey)
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h4 class="font-semibold text-green-800 dark:text-green-200">
                            <svg class="inline h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            API Key creada exitosamente
                        </h4>
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                            Copia y guarda esta clave ahora. <strong>No se mostrará de nuevo.</strong>
                        </p>
                        <code class="mt-2 block rounded bg-green-100 px-3 py-2 text-sm font-mono text-green-900 break-all dark:bg-green-800 dark:text-green-100">
                            {{ $newlyCreatedKey }}
                        </code>
                    </div>
                    <button wire:click="dismissKey" class="shrink-0 text-green-500 hover:text-green-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Create form --}}
        @if ($showCreateForm)
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Nueva API Key</h3>

                <form wire:submit="create" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Nombre descriptivo</label>
                            <input type="text" wire:model="name" class="input w-full" placeholder="Ej: Integración ERP, App móvil" />
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="label">Límite por minuto</label>
                            <input type="number" wire:model="rateLimit" class="input w-full" min="1" max="1000" />
                        </div>

                        <div>
                            <label class="label">Fecha de expiración (opcional)</label>
                            <input type="date" wire:model="expiresAt" class="input w-full" min="{{ now()->addDay()->format('Y-m-d') }}" />
                        </div>
                    </div>

                    <div>
                        <label class="label mb-2">Permisos (vacío = acceso total)</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($availablePermissions as $key => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-2 hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $key }}"
                                        class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700" />
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">Generar API Key</button>
                        <button type="button" wire:click="$set('showCreateForm', false)" class="btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex justify-end">
                <button wire:click="$set('showCreateForm', true)" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nueva API Key
                </button>
            </div>
        @endif

        {{-- Keys list --}}
        @if ($apiKeys->isEmpty() && !$showCreateForm)
            <div class="rounded-lg border-2 border-dashed border-slate-300 p-12 text-center dark:border-slate-600">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">No tienes API keys generadas</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea tu primera API key para integrar aplicaciones externas.</p>
            </div>
        @else
            <div class="card overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 font-medium">Prefijo</th>
                            <th class="px-4 py-3 font-medium">Límite/min</th>
                            <th class="px-4 py-3 font-medium">Último uso</th>
                            <th class="px-4 py-3 font-medium">Expira</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 font-medium text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($apiKeys as $key)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 {{ !$key->isValid() ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3 font-medium">{{ $key->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $key->key_prefix }}...</td>
                                <td class="px-4 py-3 tabular-nums">{{ $key->rate_limit_per_minute }}/min</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">
                                    {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Nunca' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($key->expires_at)
                                        <span class="{{ $key->isExpired() ? 'text-red-500' : 'text-slate-500' }}">
                                            {{ $key->expires_at->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Sin expiración</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($key->isExpired())
                                        <span class="badge badge-red">Expirada</span>
                                    @elseif ($key->is_active)
                                        <span class="badge badge-green">Activa</span>
                                    @else
                                        <span class="badge badge-gray">Revocada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($key->is_active && !$key->isExpired())
                                            <button wire:click="revoke({{ $key->id }})"
                                                wire:confirm="¿Revocar esta API key? Dejará de funcionar inmediatamente."
                                                class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-orange-600 dark:hover:bg-slate-700"
                                                title="Revocar">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="delete({{ $key->id }})"
                                            wire:confirm="¿Eliminar esta API key permanentemente?"
                                            class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-red-600 dark:hover:bg-slate-700"
                                            title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Usage hint --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Cómo usar tu API Key</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Incluye el header <code class="rounded bg-slate-200 px-1 dark:bg-slate-700">X-API-Key: tu_clave</code> en cada petición a
                <code class="rounded bg-slate-200 px-1 dark:bg-slate-700">/api/v1/ext/</code>
            </p>
        </div>
    @endif
</div>
