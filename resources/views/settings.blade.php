<x-app-layout>
    @php
        $user = Auth::user();
        $initial = strtoupper(substr(explode(' ', trim($user->name))[0], 0, 1));
        $field = 'w-full px-4 py-3 rounded-xl border bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
    @endphp

    {{-- .pb-safe-nav clears the mobile tab bar (it overrides the py-* bottom padding). --}}
    <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6 lg:px-8 lg:py-8 pb-safe-nav">
        <div class="mb-6 lg:mb-8">
            <p class="text-sm font-medium text-gray-600">Account preferences</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Settings</h2>
            <p class="mt-2 text-sm text-gray-600">Your account, your password, and how GTrack looks on this device.</p>
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
                <span class="ml-auto shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                    {{ $user->isSuperAdmin() ? 'Owner' : 'Staff' }}
                </span>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="account-settings-heading">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                <h3 id="account-settings-heading" class="text-sm font-semibold text-gray-900">Account</h3>
                <p class="mt-1 text-sm text-gray-500">Your sign-in and account information.</p>
            </div>

            <div class="divide-y divide-gray-100">

                {{-- ===== Name and email ===== --}}
                <div x-data="settingsProfileForm({
                        open: @js($errors->updateProfile->any()),
                        name: @js(old('name', $user->name)),
                        email: @js(old('email', $user->email)),
                        savedName: @js($user->name),
                        savedEmail: @js($user->email),
                        serverName: @js($errors->updateProfile->first('name')),
                        serverEmail: @js($errors->updateProfile->first('email')),
                     })"
                     class="px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800">Profile details</p>
                            <p class="mt-0.5 truncate text-sm text-gray-500">{{ $user->name }} · {{ $user->email }}</p>
                            @if (session('status') === 'profile-updated')
                                <p class="mt-1 text-sm font-medium text-blue-600">Saved.</p>
                            @endif
                        </div>
                        <button type="button" @click="open = ! open" :aria-expanded="open"
                                class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span x-text="open ? 'Cancel' : 'Edit'"></span>
                        </button>
                    </div>

                    <div x-cloak x-show="open" x-transition class="mt-4 border-t border-gray-100 pt-4">
                        <form method="post" action="{{ route('settings.profile') }}" class="space-y-4" @submit="submitting = true" novalidate>
                            @csrf
                            @method('patch')

                            <div>
                                <label for="settings_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Full name</label>
                                <input id="settings_name" name="name" type="text" x-model="name" @input="serverName = ''" maxlength="255" autocomplete="name"
                                       class="{{ $field }}" :class="nameError ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                                <p x-show="nameError" x-cloak x-text="nameError" class="mt-1.5 text-xs font-medium text-red-500"></p>
                            </div>

                            <div>
                                <label for="settings_email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                                <input id="settings_email" name="email" type="email" x-model="email" @input="serverEmail = ''" maxlength="255" autocomplete="username"
                                       class="{{ $field }}" :class="emailError ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                                <p x-show="emailError" x-cloak x-text="emailError" class="mt-1.5 text-xs font-medium text-red-500"></p>
                                <p x-show="! emailError" class="mt-1.5 text-xs text-gray-500">This is also how you sign in, and where password links are sent.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button type="submit" :disabled="!! blockedReason || submitting"
                                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Save changes
                                </button>
                                <p x-show="blockedReason && ! nameError && ! emailError" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ===== Password ===== --}}
                <div x-data="{ open: {{ $errors->updatePassword->any() || session('status') === 'password-updated' ? 'true' : 'false' }} }" class="px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-1.5 0h12a1.5 1.5 0 011.5 1.5v7.5A1.5 1.5 0 0116.5 21h-9A1.5 1.5 0 016 19.5V12a1.5 1.5 0 011.5-1.5z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">Password</p>
                            <p class="mt-0.5 text-sm text-gray-500">Keep your account secure with a strong password.</p>
                            @if (session('status') === 'password-updated')
                                <p class="mt-1 text-sm font-medium text-blue-600">Password updated.</p>
                            @endif
                        </div>
                        <button type="button" @click="open = ! open" :aria-expanded="open"
                                class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span x-text="open ? 'Cancel' : 'Change'"></span>
                        </button>
                    </div>

                    <div x-cloak x-show="open" x-transition class="mt-4 border-t border-gray-100 pt-4">
                        {{-- Same behaviour as the set-password page: eye, weak → strong
                             bar, live checks, and a Save that says why it's disabled. --}}
                        <form method="post" action="{{ route('password.update') }}" class="space-y-4"
                              x-data="updatePasswordForm({
                                  serverCurrent: @js($errors->updatePassword->first('current_password')),
                                  serverPassword: @js($errors->updatePassword->first('password')),
                              })"
                              @submit="submitting = true" novalidate>
                            @csrf
                            @method('put')

                            <div>
                                <label for="settings_current_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Current password</label>
                                <x-password-input id="settings_current_password" name="current_password" model="current"
                                                  autocomplete="current-password" invalid="!! serverCurrent" @input="serverCurrent = ''" />
                                <p x-show="serverCurrent" x-cloak x-text="serverCurrent" class="mt-1.5 text-xs font-medium text-red-500"></p>
                            </div>

                            <div>
                                <label for="settings_password" class="block text-sm font-semibold text-gray-700 mb-1.5">New password</label>
                                <x-password-input id="settings_password" name="password" model="password"
                                                  invalid="tooShort || !! serverPassword" @input="serverPassword = ''" />
                                <x-password-strength value="password" />
                                <p x-show="serverPassword" x-cloak x-text="serverPassword" class="mt-1.5 text-xs font-medium text-red-500"></p>
                            </div>

                            <div>
                                <label for="settings_password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm new password</label>
                                <x-password-input id="settings_password_confirmation" name="password_confirmation" model="confirmation"
                                                  invalid="mismatch" @input="serverPassword = ''" />
                                <p x-show="mismatch" x-cloak class="mt-1.5 text-xs font-medium text-red-500">Doesn't match yet.</p>
                                <p x-show="matches" x-cloak class="mt-1.5 flex items-center gap-1 text-xs font-medium text-blue-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    Passwords match
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button type="submit" :disabled="!! blockedReason || submitting"
                                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Update password
                                </button>
                                <p x-show="showReason" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ===== Other devices ===== --}}
                <div x-data="{ open: {{ $errors->signOutOthers->any() ? 'true' : 'false' }}, confirmation: '' }" class="px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800">Other devices</p>
                            <p class="mt-0.5 text-sm text-gray-500">
                                @if ($otherDevices === null)
                                    Signing out other devices isn't available on this setup.
                                @elseif ($otherDevices['count'] === 0)
                                    You're only signed in on this device.
                                @else
                                    Signed in on {{ $otherDevices['count'] }} other {{ Str::plural('device', $otherDevices['count']) }}@if ($otherDevices['last_active']) · last active {{ $otherDevices['last_active']->diffForHumans() }}@endif.
                                @endif
                            </p>
                            @if (session('status') === 'signed-out-others')
                                <p class="mt-1 text-sm font-medium text-blue-600">Signed out everywhere else.</p>
                            @endif
                        </div>
                        @if ($otherDevices !== null && $otherDevices['count'] > 0)
                            <button type="button" @click="open = ! open" :aria-expanded="open"
                                    class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span x-text="open ? 'Cancel' : 'Sign out'"></span>
                            </button>
                        @endif
                    </div>

                    @if ($otherDevices !== null && $otherDevices['count'] > 0)
                        <div x-cloak x-show="open" x-transition class="mt-4 border-t border-gray-100 pt-4">
                            <form method="post" action="{{ route('settings.sign-out-others') }}" class="space-y-4" novalidate>
                                @csrf
                                <div>
                                    <label for="settings_signout_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Your current password</label>
                                    <x-password-input id="settings_signout_password" name="current_password" model="confirmation"
                                                      autocomplete="current-password"
                                                      invalid="{{ $errors->signOutOthers->has('current_password') ? 'true' : 'false' }}" />
                                    @if ($errors->signOutOthers->has('current_password'))
                                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $errors->signOutOthers->first('current_password') }}</p>
                                    @endif
                                    <p class="mt-1.5 text-xs text-gray-500">This device stays signed in. Everyone else has to sign in again.</p>
                                </div>
                                <button type="submit" :disabled="! confirmation"
                                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    Sign out other devices
                                </button>
                                <p x-show="! confirmation" x-cloak class="text-sm text-gray-500">Enter your password to continue.</p>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Owner only: rates, quick amounts, auto-close, and the backup. --}}
        @if ($shop)
            @include('settings.partials.owner', ['shop' => $shop])
        @endif

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
    </div>

    <script>
        // Name + email. Mirrors the server rules (required name, a valid email);
        // the server re-checks, and also refuses an email that's already taken.
        function settingsProfileForm(config) {
            return {
                open: config.open,
                name: config.name,
                email: config.email,
                savedName: config.savedName,
                savedEmail: config.savedEmail,
                serverName: config.serverName || '',
                serverEmail: config.serverEmail || '',
                submitting: false,

                get nameError() {
                    if (this.serverName) return this.serverName;

                    return this.name.trim() === '' ? 'Enter your name.' : '';
                },

                get emailError() {
                    if (this.serverEmail) return this.serverEmail;

                    const value = this.email.trim();

                    if (value === '') return 'Enter your email.';

                    return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(value) ? '' : 'That doesn\'t look like an email address.';
                },

                get unchanged() {
                    return this.name.trim() === this.savedName
                        && this.email.trim().toLowerCase() === this.savedEmail.toLowerCase();
                },

                get blockedReason() {
                    if (this.nameError) return this.nameError;
                    if (this.emailError) return this.emailError;
                    if (this.unchanged) return 'Change your name or email to save.';

                    return '';
                },
            };
        }
    </script>
</x-app-layout>
