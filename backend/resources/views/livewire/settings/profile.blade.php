<div>
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('tenant.settings.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Configuración
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="size-5 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500 dark:text-gray-400">Mi Perfil</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Mi Perfil</h1>
    </div>

    <div class="space-y-6">
        <!-- Profile Form -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Información Personal</h3>
            </div>
            <form wire:submit="updateProfile" class="px-4 py-5 sm:p-6">
                @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
                @endif

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                        <input wire:model="name" type="text" id="name"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input wire:model="email" type="email" id="email" disabled
                            class="mt-1 block w-full rounded-md border-0 bg-gray-100 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 dark:bg-gray-600 dark:text-gray-400 dark:ring-gray-600 sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">El email no puede ser modificado</p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                        <input wire:model="phone" type="text" id="phone"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Form -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Cambiar Contraseña</h3>
            </div>
            <form wire:submit="updatePassword" class="px-4 py-5 sm:p-6">
                @if(session('password_success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('password_success') }}</p>
                </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña Actual</label>
                        <input wire:model="current_password" type="password" id="current_password"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                        @error('current_password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Contraseña</label>
                        <input wire:model="password" type="password" id="password"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                        @error('password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Contraseña</label>
                        <input wire:model="password_confirmation" type="password" id="password_confirmation"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                        Actualizar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
