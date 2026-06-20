<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>GTrack</title>

        {{-- Favicon --}}
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }

            .brand-wordmark {
                font-family: 'Inter', sans-serif;
                font-weight: 800;
                letter-spacing: -0.045em;
            }

            /* ===== Animated gradient backdrop (subtle, alive) ===== */
            .brand-panel {
                background: linear-gradient(135deg, #f8fafc, #eaf1ff, #e3edff, #eafff6, #eef4ff);
                background-size: 300% 300%;
                animation: gradientShift 16s ease infinite;
            }
            @keyframes gradientShift {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }

            /* ===== Floating colored accents ===== */
            .orb-a { animation: floatA 9s ease-in-out infinite; }
            .orb-b { animation: floatB 12s ease-in-out infinite; }
            @keyframes floatA {
                0%, 100% { transform: translate(0, 0); }
                50%      { transform: translate(24px, -34px); }
            }
            @keyframes floatB {
                0%, 100% { transform: translate(0, 0); }
                50%      { transform: translate(-28px, 22px); }
            }

            /* ===== Entrance + continuous motion (always on) ===== */
            @keyframes popIn {
                0%   { opacity: 0; transform: scale(0.55); }
                60%  { opacity: 1; transform: scale(1.06); }
                100% { transform: scale(1); }
            }
            @keyframes riseIn {
                from { opacity: 0; transform: translateY(16px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            @keyframes lineGrow {
                from { width: 0; opacity: 0; }
                to   { width: 3rem; opacity: 1; }
            }
            @keyframes bob {
                0%, 100% { transform: translateY(0); }
                50%      { transform: translateY(-8px); }
            }

            .an-pop  { animation: popIn 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }
            .an-rise { animation: riseIn 0.6s cubic-bezier(0.22, 1, 0.36, 1) both; }
            .an-line { animation: lineGrow 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }
            .logo-bob { animation: bob 4.5s ease-in-out infinite; animation-delay: 0.9s; }

            .d1 { animation-delay: 0.12s; }
            .d2 { animation-delay: 0.26s; }
            .d3 { animation-delay: 0.38s; }
            .d4 { animation-delay: 0.50s; }
        </style>
    </head>
    <body class="antialiased bg-white">
        @include('partials.loader')

        <div class="min-h-screen lg:grid lg:grid-cols-2">

            {{-- ===== LEFT — animated branding (hidden on mobile) ===== --}}
            <div class="brand-panel hidden lg:flex relative items-center justify-center overflow-hidden">

                {{-- floating colored accents --}}
                <div class="orb-a absolute w-[30rem] h-[30rem] rounded-full blur-3xl opacity-50 -top-24 -left-24"
                     style="background: radial-gradient(circle, #93c5fd 0%, transparent 70%);"></div>
                <div class="orb-b absolute w-96 h-96 rounded-full blur-3xl opacity-40 -bottom-20 -right-16"
                     style="background: radial-gradient(circle, #6ee7b7 0%, transparent 70%);"></div>
                <div class="orb-a absolute w-72 h-72 rounded-full blur-3xl opacity-30 top-1/3 right-1/4"
                     style="background: radial-gradient(circle, #c4b5fd 0%, transparent 70%); animation-delay: -3s;"></div>

                <div class="relative text-center px-12">
                    <div class="an-pop inline-block mb-6">
                        <img src="{{ asset('logo.png') }}" alt="GTrack" class="logo-bob w-20 h-20 mx-auto drop-shadow-md">
                    </div>
                    <h1 class="brand-wordmark an-rise d1 text-5xl text-gray-900 mb-4">GTrack</h1>
                    <div class="an-line d2 h-1 rounded-full mx-auto mb-5" style="background: linear-gradient(90deg, #0070FF, #00C2FF);"></div>
                    <p class="an-rise d3 text-gray-500 text-base max-w-xs mx-auto leading-relaxed">
                        Manage your GCash cash-in and cash-out transactions in one place.
                    </p>
                </div>
            </div>

            {{-- ===== RIGHT — form ===== --}}
            <div class="flex flex-col items-center justify-center min-h-screen px-6 py-12 bg-white">

                {{-- mobile logo --}}
                <div class="lg:hidden mb-8 text-center an-rise">
                    <img src="{{ asset('logo.png') }}" alt="GTrack" class="w-14 h-14 mx-auto mb-2">
                    <h1 class="brand-wordmark text-2xl text-gray-900">GTrack</h1>
                </div>

                <div class="w-full max-w-sm an-rise d2">
                    {{ $slot }}
                </div>

                <p class="mt-10 text-gray-300 text-xs an-rise d4">© {{ date('Y') }} GTrack by Tristan &amp; Lhenna</p>
            </div>
        </div>
    </body>
</html>
