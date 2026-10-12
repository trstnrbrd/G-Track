<x-guest-layout>

    {{-- Used for both "I forgot my password" and the welcome email's "Set your
         password" link — same token, same page. Validation reacts as you type
         and a disabled button says why. --}}

    <h2 class="text-xl font-bold text-gray-900 mb-0.5">Set your password</h2>
    <p class="text-gray-400 text-sm mb-7 font-normal">Choose one you'll remember. At least 8 characters.</p>

    <form method="POST" action="{{ route('password.store') }}"
          x-data="resetPasswordForm({
              serverPassword: @js($errors->first('password')),
              serverEmail: @js($errors->first('email')),
          })"
          @submit="submitting = true" novalidate>
        @csrf

        {{-- Password reset token --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                @if (old('email', $request->email)) readonly @endif
                autocomplete="username"
                @input="serverEmail = ''"
                class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition read-only:text-gray-500 read-only:cursor-default"
                :class="serverEmail ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'"
            />
            <p x-show="serverEmail" x-cloak x-text="serverEmail" class="text-red-500 text-xs mt-1.5 font-medium"></p>
        </div>

        {{-- New password --}}
        <div class="mb-4">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">New password</label>
            <x-password-input
                name="password"
                model="password"
                placeholder="Enter a new password"
                invalid="tooShort || !! serverPassword"
                autofocus
                @input="serverPassword = ''"
            />
            <x-password-strength value="password" />
            <p x-show="serverPassword" x-cloak x-text="serverPassword" class="text-red-500 text-xs mt-1.5 font-medium"></p>
        </div>

        {{-- Confirm --}}
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm password</label>
            <x-password-input
                name="password_confirmation"
                model="confirmation"
                placeholder="Type it again"
                invalid="!! mismatch"
                @input="serverPassword = ''"
            />
            <p x-show="mismatch" x-cloak class="text-red-500 text-xs mt-1.5 font-medium">Doesn't match yet.</p>
            <p x-show="matches" x-cloak class="flex items-center gap-1 text-gcash-600 text-xs mt-1.5 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                Passwords match
            </p>
        </div>

        <button
            type="submit"
            :disabled="!! blockedReason || submitting"
            class="w-full py-3 px-4 rounded-xl font-semibold text-white text-sm tracking-wide shadow-md transition duration-200 hover:opacity-90 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
            style="background: linear-gradient(90deg, #0070FF 0%, #00AAFF 100%);"
        >
            Set password
        </button>

        {{-- A disabled button should say why. --}}
        <p x-show="showReason" x-cloak x-text="blockedReason" class="mt-3 text-center text-xs text-gray-500"></p>
    </form>

    <script>
        function resetPasswordForm(config) {
            const MIN = 8;

            return {
                password: '',
                confirmation: '',
                serverPassword: config.serverPassword || '',
                serverEmail: config.serverEmail || '',
                submitting: false,

                // Mirrors the server's min:8 and `confirmed` rules. The server
                // still re-checks both. The strength bar already says "N more
                // characters to go", so there is no second message for length.
                get tooShort() {
                    return this.password.length > 0 && this.password.length < MIN;
                },

                get mismatch() {
                    return this.confirmation.length > 0 && this.confirmation !== this.password;
                },

                get matches() {
                    return this.password.length >= MIN && this.confirmation === this.password;
                },

                get blockedReason() {
                    if (! this.password) return 'Enter a new password to continue.';
                    if (this.password.length < MIN) return 'Use at least ' + MIN + ' characters.';
                    if (! this.confirmation) return 'Type it again to confirm.';
                    if (this.mismatch) return "The two passwords don't match yet.";

                    return '';
                },

                // Say it under the button only when nothing above already has.
                get showReason() {
                    return !! this.blockedReason && ! this.tooShort && ! this.mismatch;
                },
            };
        }
    </script>

</x-guest-layout>
