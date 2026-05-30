<x-guest-layout>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-2xl font-bold text-gray-800 mb-1">Welcome back!</h2>
    <p class="text-gray-400 text-sm mb-7">Sign in to your GTrack account</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                required
                autofocus
                autocomplete="username"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div class="mb-5">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Remember Me + Forgot Password --}}
        <div class="flex items-center justify-between mb-7">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                />
                <span class="text-sm text-gray-600">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 transition">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full py-3.5 px-4 rounded-xl font-bold text-white text-base shadow-lg transition duration-200 hover:opacity-90 active:scale-95"
            style="background: linear-gradient(90deg, #0070FF 0%, #00AAFF 100%);"
        >
            Log In
        </button>
    </form>

</x-guest-layout>
