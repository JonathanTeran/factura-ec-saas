<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Portal de Documentos' }} - AmePhia Facturacion</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <a href="{{ route('portal.dashboard') }}" class="flex shrink-0 items-center">
                            <img src="{{ asset('images/amelogo_v3_optimized.webp') }}" alt="AmePhia" class="h-8 object-contain">
                        </a>
                        @isset($portalSession)
                        <div class="ml-6 hidden space-x-4 sm:flex">
                            <a href="{{ route('portal.dashboard') }}"
                               class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('portal.dashboard') ? 'border-b-2 border-blue-500 text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white' }}">
                                Inicio
                            </a>
                            <a href="{{ route('portal.documents.index') }}"
                               class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('portal.documents.*') ? 'border-b-2 border-blue-500 text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white' }}">
                                Documentos
                            </a>
                        </div>
                        @endisset
                    </div>

                    @isset($portalSession)
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $portalSession->customer_name }}
                        </span>
                        <form method="POST" action="{{ route('portal.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                Cerrar Sesion
                            </button>
                        </form>
                    </div>
                    @endisset
                </div>
            </div>

            <!-- Mobile nav -->
            @isset($portalSession)
            <div class="border-t border-gray-200 sm:hidden dark:border-gray-700">
                <div class="flex space-x-4 px-4 py-2">
                    <a href="{{ route('portal.dashboard') }}"
                       class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('portal.dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-300' }}">
                        Inicio
                    </a>
                    <a href="{{ route('portal.documents.index') }}"
                       class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('portal.documents.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-300' }}">
                        Documentos
                    </a>
                </div>
            </div>
            @endisset
        </nav>

        <!-- Content -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs text-gray-400 dark:text-gray-500">
                    Portal de Documentos Electronicos &copy; {{ date('Y') }} AmePhia
                </p>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
