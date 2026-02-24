<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Iniciar Sesion' }} - AmePhia Facturacion</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <img src="{{ asset('images/amelogo_v3_optimized.webp') }}" alt="AmePhia Facturacion" class="h-10 object-contain">
            </div>
            @if(isset($subtitle))
            <h2 class="mt-6 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $subtitle }}
            </h2>
            @endif
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-4 py-8 shadow sm:rounded-lg sm:px-10 dark:bg-gray-800">
                {{ $slot }}
            </div>

            @if(isset($footer))
            <div class="mt-6 text-center">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>
