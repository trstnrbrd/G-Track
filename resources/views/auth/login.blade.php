<x-guest-layout>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-xl font-bold text-gray-900 mb-0.5">Sign in</h2>
    <p class="text-gray-400 text-sm mb-7 font-normal">Enter your credentials to continue</p>

    <form method="POST" action="{{ route('login') }}" x-data="loginForm()" @submit="onSubmit($event)" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                x-model="email"
                @input="errors.email = ''"
                placeholder="Enter your email"
                autofocus
                autocomplete="username"
                class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                :class="errors.email ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'"
            />
            {{-- Client-side validation message --}}
            <p x-show="errors.email" x-cloak x-text="errors.email" class="flex items-center gap-1 text-red-500 text-xs mt-1.5 font-medium"></p>
            {{-- Server-side validation message --}}
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div class="mb-5">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
            <div class="relative">
                <input
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    x-model="password"
                    @input="errors.password = ''"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    class="w-full px-4 py-3 pr-12 rounded-xl border bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    :class="errors.password ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'"
                />
                <button
                    type="button"
                    @click="show = !show"
                    :aria-label="show ? 'Hide password' : 'Show password'"
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition focus:outline-none"
                >
                    {{-- Eye (show) --}}
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{-- Eye-off (hide) --}}
                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 002.25 12s3.75 7.5 9.75 7.5c1.32 0 2.57-.26 3.72-.72M6.53 6.53A9.96 9.96 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a13.46 13.46 0 01-2.51 3.47M6.53 6.53L3 3m3.53 3.53l3.94 3.94m0 0a3 3 0 104.24 4.24m-4.24-4.24l4.24 4.24m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <p x-show="errors.password" x-cloak x-text="errors.password" class="flex items-center gap-1 text-red-500 text-xs mt-1.5 font-medium"></p>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Remember Me + Forgot Password --}}
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                />
                <span class="text-sm text-gray-500">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl font-semibold text-white text-sm tracking-wide shadow-md transition duration-200 hover:opacity-90 active:scale-95"
            style="background: linear-gradient(90deg, #0070FF 0%, #00AAFF 100%);"
        >
            Sign In
        </button>
    </form>

    <script>
        function loginForm() {
            return {
                email: @js(old('email', '')),
                password: '',
                show: false,
                errors: {},
                onSubmit(e) {
                    this.errors = {};

                    if (! this.email.trim()) {
                        this.errors.email = 'Please enter your email address.';
                    } else if (! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.email.trim())) {
                        this.errors.email = 'Please enter a valid email address.';
                    }

                    if (! this.password) {
                        this.errors.password = 'Please enter your password.';
                    }

                    if (Object.keys(this.errors).length > 0) {
                        e.preventDefault();
                        e.stopPropagation();   // keep the global loader/spinner from firing
                    }
                },
            };
        }
    </script>

</x-guest-layout>
