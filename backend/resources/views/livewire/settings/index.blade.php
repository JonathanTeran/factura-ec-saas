<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Configuración</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gestiona la configuración de tu cuenta</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Profile -->
        <a href="{{ route('tenant.settings.profile') }}"
            class="flex items-start rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800">
            <div class="flex size-12 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                <svg class="size-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mi Perfil</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Actualiza tu información personal y contraseña
                </p>
            </div>
        </a>

        <!-- Company -->
        <a href="{{ route('tenant.settings.company') }}"
            class="flex items-start rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800">
            <div class="flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Empresas</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gestiona tus empresas, RUCs y firma electrónica
                </p>
            </div>
        </a>

        <!-- Subscription -->
        <a href="{{ route('tenant.settings.subscription') }}"
            class="flex items-start rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800">
            <div class="flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Suscripción</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gestiona tu plan y métodos de pago
                </p>
            </div>
        </a>

        <!-- Users (coming soon) -->
        <div class="flex items-start rounded-lg bg-white p-6 opacity-60 shadow dark:bg-gray-800">
            <div class="flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                <svg class="size-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Usuarios</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Próximamente - Gestiona usuarios y permisos
                </p>
            </div>
        </div>
    </div>
</div>
