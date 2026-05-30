<x-guest-layout>

    <h2 class="text-2xl font-bold text-gray-800 mb-1">Forgot password?</h2>
    <p class="text-gray-400 text-sm mb-7 leading-relaxed">
        No problem. Enter your email and we'll send you a reset link to choose a new password.
    </p>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-6">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                required
                autofocus
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full py-3.5 px-4 rounded-xl font-bold text-white text-base shadow-lg transition duration-200 hover:opacity-90 active:scale-95"
            style="background: linear-gradient(90deg, #0070FF 0%, #00AAFF 100%);"
        >
            Send Reset Link
        </button>
    </form>

    {{-- Back to login --}}
    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-blue-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Back to login
        </a>
    </div>

</x-guest-layout>
