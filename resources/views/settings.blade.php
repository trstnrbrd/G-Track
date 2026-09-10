<x-app-layout>
    @php
        $user = Auth::user();
        $initial = strtoupper(substr(explode(' ', trim($user->name))[0], 0, 1));
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mb-6 lg:mb-8">
            <p class="text-sm font-medium text-gray-400">Account preferences</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Settings</h2>
            <p class="mt-2 text-sm text-gray-500">View your account details and preferences.</p>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="account-heading">
            <div class="flex items-center gap-4 px-5 py-5 sm:px-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-lg font-bold text-white">
                    {{ $initial }}
                </div>
                <div class="min-w-0">
                    <h3 id="account-heading" class="truncate text-base font-semibold text-gray-900">{{ $user->name }}</h3>
                    <p class="mt-0.5 truncate text-sm text-gray-500">{{ $user->email }}</p>
                </div>
                <span class="ml-auto shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold capitalize text-blue-700">
                    {{ str_replace('_', ' ', $user->role) }}
                </span>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="account-settings-heading">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                <h3 id="account-settings-heading" class="text-sm font-semibold text-gray-900">Account</h3>
                <p class="mt-1 text-sm text-gray-500">Your sign-in and account information.</p>
            </div>

            <div class="divide-y divide-gray-100">
                <div class="flex items-center gap-3 px-5 py-4 sm:px-6">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5h.008v.008H16.5V7.5zM8.25 7.5h.008v.008H8.25V7.5zM9 12h6m-6 0a3 3 0 106 0m-6 0H7.5a3 3 0 00-3 3v1.5A1.5 1.5 0 006 18h12a1.5 1.5 0 001.5-1.5V15a3 3 0 00-3-3H15m-6 0V9a3 3 0 016 0v3"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800">Profile details</p>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $user->name }} · {{ $user->email }}</p>
                    </div>
                    <span class="text-xs font-medium text-gray-400">View only</span>
                </div>

                <div class="flex items-center gap-3 px-5 py-4 sm:px-6">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-1.5 0h12a1.5 1.5 0 011.5 1.5v7.5A1.5 1.5 0 0116.5 21h-9A1.5 1.5 0 016 19.5V12a1.5 1.5 0 011.5-1.5z"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">Password</p>
                        <p class="mt-0.5 text-sm text-gray-500">Keep your account secure with a strong password.</p>
                    </div>
                    <span class="text-xs font-medium text-gray-400">Coming soon</span>
                </div>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="app-settings-heading">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                <h3 id="app-settings-heading" class="text-sm font-semibold text-gray-900">App</h3>
                <p class="mt-1 text-sm text-gray-500">Settings for how GTrack works on this device.</p>
            </div>

            <div x-data="{ theme: localStorage.getItem('gtrack-theme') || 'system', setTheme(value) { this.theme = value; window.setGTrackTheme(value); } }" class="px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m6.364-15.364l-1.06 1.06M6.696 17.304l-1.06 1.06m12.728 0l-1.06-1.06M6.696 6.696l-1.06-1.06M21 12h-1.5M4.5 12H3m12.75 0a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800">Theme</p>
                        <p class="mt-0.5 text-sm text-gray-500">Choose how GTrack looks on this device.</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2" role="radiogroup" aria-label="Theme preference">
                    <button type="button" @click="setTheme('light')" :aria-checked="theme === 'light'" role="radio"
                            :class="theme === 'light' ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m6.364-15.364l-1.06 1.06M6.696 17.304l-1.06 1.06m12.728 0l-1.06-1.06M6.696 6.696l-1.06-1.06M21 12h-1.5M4.5 12H3m12.75 0a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                        Light
                    </button>
                    <button type="button" @click="setTheme('dark')" :aria-checked="theme === 'dark'" role="radio"
                            :class="theme === 'dark' ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a5.25 5.25 0 006.75 5.032A6.75 6.75 0 1112.218 5.25 5.22 5.22 0 0012 6.75z"/></svg>
                        Dark
                    </button>
                    <button type="button" @click="setTheme('system')" :aria-checked="theme === 'system'" role="radio"
                            :class="theme === 'system' ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17.25v-1.5a2.25 2.25 0 012.25-2.25h0a2.25 2.25 0 012.25 2.25v1.5m-7.5 0h10.5m-9-10.5h7.5A2.25 2.25 0 0118 9v6.75H6V9a2.25 2.25 0 012.25-2.25z"/></svg>
                        System
                    </button>
                </div>
            </div>
        </section>

        <p class="mt-5 px-1 text-xs leading-5 text-gray-400">Editing options will be available here in a future update.</p>
    </div>
</x-app-layout>
