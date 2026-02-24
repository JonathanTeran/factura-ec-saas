<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Webhooks</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Recibe notificaciones en tiempo real cuando ocurren eventos en tus documentos.
            </p>
        </div>
        <a href="{{ route('panel.settings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Configuración
        </a>
    </div>

    @if (!$hasFeature)
        {{-- Plan upgrade required --}}
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-yellow-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Funcionalidad no disponible</h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        Los webhooks están disponibles a partir del plan Negocio. Actualiza tu plan para recibir notificaciones en tiempo real.
                    </p>
                    <a href="{{ route('panel.settings.billing') }}" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-yellow-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-yellow-700">
                        Actualizar plan
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Secret display alert --}}
        @if ($displayedSecret)
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-semibold text-green-800 dark:text-green-200">Secret del Webhook</h4>
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">Guarda este secret. No se mostrará de nuevo.</p>
                        <code class="mt-2 block rounded bg-green-100 px-3 py-2 text-sm font-mono text-green-900 dark:bg-green-800 dark:text-green-100">
                            {{ $displayedSecret }}
                        </code>
                    </div>
                    <button wire:click="dismissSecret" class="text-green-500 hover:text-green-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Create/Edit form --}}
        @if ($showCreateForm)
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $editingId ? 'Editar Webhook' : 'Nuevo Webhook' }}
                </h3>

                <form wire:submit="{{ $editingId ? 'update' : 'create' }}" class="space-y-4">
                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL del endpoint</label>
                        <input type="url" wire:model="url" id="url" placeholder="https://tu-app.com/webhook"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        @error('url') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Eventos</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($availableEvents as $key => $label)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedEvents" value="{{ $key }}"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedEvents') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ $editingId ? 'Actualizar' : 'Crear Webhook' }}
                        </button>
                        <button type="button" wire:click="cancelForm"
                            class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex justify-end">
                <button wire:click="$set('showCreateForm', true)"
                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Webhook
                </button>
            </div>
        @endif

        {{-- Endpoints list --}}
        @if ($endpoints->isEmpty() && !$showCreateForm)
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tienes webhooks configurados</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crea tu primer webhook para recibir notificaciones.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($endpoints as $endpoint)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    {{-- Status indicator --}}
                                    @if ($endpoint->isDisabledDueToFailures())
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">
                                            Deshabilitado
                                        </span>
                                    @elseif ($endpoint->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            Inactivo
                                        </span>
                                    @endif

                                    @if ($endpoint->failure_count > 0 && !$endpoint->isDisabledDueToFailures())
                                        <span class="text-xs text-orange-500">{{ $endpoint->failure_count }} errores</span>
                                    @endif
                                </div>

                                <p class="mt-1 truncate font-mono text-sm text-gray-900 dark:text-white">{{ $endpoint->url }}</p>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($endpoint->events as $event)
                                        <span class="inline-flex rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            {{ $availableEvents[$event] ?? $event }}
                                        </span>
                                    @endforeach
                                </div>

                                @if ($endpoint->last_triggered_at)
                                    <p class="mt-1 text-xs text-gray-400">
                                        Ultimo envio: {{ $endpoint->last_triggered_at->diffForHumans() }}
                                    </p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 ml-4">
                                <button wire:click="edit({{ $endpoint->id }})" title="Editar"
                                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>

                                <button wire:click="toggleActive({{ $endpoint->id }})" title="{{ $endpoint->is_active ? 'Desactivar' : 'Activar' }}"
                                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                    @if ($endpoint->is_active)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V18M15 7.5V18M3 16.811V8.69c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811Z" />
                                        </svg>
                                    @endif
                                </button>

                                <button wire:click="regenerateSecret({{ $endpoint->id }})" title="Regenerar secret"
                                    wire:confirm="Esto invalidará el secret actual. ¿Continuar?"
                                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-orange-600 dark:hover:bg-gray-700 dark:hover:text-orange-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                    </svg>
                                </button>

                                @if ($endpoint->isDisabledDueToFailures())
                                    <button wire:click="resetFailures({{ $endpoint->id }})" title="Reactivar"
                                        class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-green-600 dark:hover:bg-gray-700 dark:hover:text-green-400">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                        </svg>
                                    </button>
                                @endif

                                <button wire:click="delete({{ $endpoint->id }})" title="Eliminar"
                                    wire:confirm="¿Eliminar este webhook?"
                                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-gray-700 dark:hover:text-red-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Documentation hint --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Verificacion de firma</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Cada webhook incluye un header <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">X-Webhook-Signature</code>
                con HMAC-SHA256 del body. Verifica la firma usando tu secret para asegurar la autenticidad.
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Formula: <code class="rounded bg-gray-200 px-1 dark:bg-gray-700">HMAC-SHA256("{timestamp}.{body}", secret)</code>
            </p>
        </div>
    @endif
</div>
