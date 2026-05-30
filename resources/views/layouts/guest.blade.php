<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>GTrack</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4" style="background: linear-gradient(160deg, #0070FF 0%, #00AAFF 60%, #00D4AA 100%);">

            {{-- Branding --}}
            <div class="mb-8 text-center">
                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                    <span class="text-4xl font-extrabold text-blue-600">G</span>
                </div>
                <h1 class="text-white text-3xl font-extrabold tracking-tight">GTrack</h1>
                <p class="text-blue-100 text-sm mt-1 font-medium">GCash Transaction Manager</p>
            </div>

            {{-- Card --}}
            <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl px-6 py-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-blue-100 text-xs">© {{ date('Y') }} GTrack by Tristan Reboredo</p>
        </div>
    </body>
</html>
