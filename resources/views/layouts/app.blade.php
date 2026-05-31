<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GTrack') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-50">
        @include('partials.loader')

        @php
            $firstName = explode(' ', trim(Auth::user()->name))[0];
            $nav = [
                ['label' => 'Dashboard',      'url' => route('dashboard'), 'active' => request()->routeIs('dashboard'),
                 'icon' => 'M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L21 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
                ['label' => 'Transactions',   'url' => '#', 'active' => false,
                 'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
                ['label' => 'History',        'url' => '#', 'active' => false,
                 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Balance',        'url' => '#', 'active' => false,
                 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z'],
                ['label' => 'Reconciliation', 'url' => '#', 'active' => false,
                 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'],
            ];
            if (Auth::user()->isSuperAdmin()) {
                $nav[] = ['label' => 'User Management', 'url' => '#', 'active' => false,
                    'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'];
            }
            $activeLabel = collect($nav)->firstWhere('active', true)['label'] ?? 'Dashboard';

            // Mobile bottom bar: 4 primary tabs + a "More" sheet for the rest
            $primaryNav = array_slice($nav, 0, 4);
            $moreNav    = array_slice($nav, 4);
            $moreActive = collect($moreNav)->contains('active', true);
        @endphp

        {{-- ===== Sidebar (desktop only) ===== --}}
        <aside class="hidden lg:flex lg:flex-col fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-100 z-40">

            {{-- Logo --}}
            <div class="h-16 flex items-center px-5 border-b border-gray-100">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('logo.png') }}" alt="GTrack" class="w-8 h-8">
                    <span class="brand-wordmark text-xl text-gray-900">GTrack</span>
                </a>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ $item['active']
                                  ? 'bg-blue-50 text-blue-600'
                                  : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Bottom: user info --}}
            <div class="border-t border-gray-100 p-3">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white shrink-0"
                         style="background: linear-gradient(135deg, #0066FF, #00A6FF);">
                        {{ strtoupper(substr($firstName, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', Auth::user()->role) }}</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ===== Main area ===== --}}
        <div class="lg:pl-64 min-h-screen flex flex-col">

            {{-- Topbar --}}
            <header class="sticky top-0 z-30 h-16 bg-white/90 backdrop-blur border-b border-gray-100 flex items-center justify-between px-4 lg:px-8">
                <h1 class="text-lg font-bold text-gray-900">{{ $activeLabel }}</h1>

                {{-- Account dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white"
                            style="background: linear-gradient(135deg, #0066FF, #00A6FF);">
                        {{ strtoupper(substr($firstName, 0, 1)) }}
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl ring-1 ring-black/5 py-1.5 z-40">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', Auth::user()->role) }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">Log out</button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Page content (extra bottom space on mobile for the tab bar) --}}
            <main class="flex-1 pb-20 lg:pb-0">
                {{ $slot }}
            </main>
        </div>

        {{-- ===== Mobile bottom tab bar ===== --}}
        <div x-data="{ moreOpen: false }" class="lg:hidden">

            {{-- "More" bottom sheet --}}
            <div x-show="moreOpen" x-cloak class="fixed inset-0 z-50">
                <div x-show="moreOpen" x-transition.opacity @click="moreOpen = false" class="absolute inset-0 bg-black/40"></div>
                <div x-show="moreOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                     class="absolute bottom-0 inset-x-0 bg-white rounded-t-3xl p-4 pb-8">
                    <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-4"></div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-2 mb-2">More</p>
                    @foreach ($moreNav as $item)
                        <a href="{{ $item['url'] }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium
                                  {{ $item['active'] ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Tab bar --}}
            <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-100 z-40 flex">
                @foreach ($primaryNav as $item)
                    <a href="{{ $item['url'] }}"
                       class="relative flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition
                              {{ $item['active'] ? 'text-blue-600' : 'text-gray-400' }}">
                        @if ($item['active'])
                            <span class="absolute top-0 w-8 h-[3px] rounded-b-full bg-blue-600"></span>
                        @endif
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="text-[10px] font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                {{-- More tab --}}
                <button @click="moreOpen = true"
                        class="relative flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition
                               {{ $moreActive ? 'text-blue-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM12 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM18 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                    </svg>
                    <span class="text-[10px] font-medium">More</span>
                </button>
            </nav>
        </div>
    </body>
</html>
