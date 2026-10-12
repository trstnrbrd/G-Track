<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    {{-- Same behaviour as the set-password page: show/hide eye, a weak → strong
         bar, live checks, and a disabled Save that says why. The server still
         re-checks everything. --}}
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6"
          x-data="updatePasswordForm({
              serverCurrent: @js($errors->updatePassword->first('current_password')),
              serverPassword: @js($errors->updatePassword->first('password')),
          })"
          @submit="submitting = true" novalidate>
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="mt-1">
                <x-password-input
                    id="update_password_current_password"
                    name="current_password"
                    model="current"
                    autocomplete="current-password"
                    invalid="!! serverCurrent"
                    @input="serverCurrent = ''"
                />
            </div>
            <p x-show="serverCurrent" x-cloak x-text="serverCurrent" class="mt-2 text-sm text-red-600"></p>
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="mt-1">
                <x-password-input
                    id="update_password_password"
                    name="password"
                    model="password"
                    invalid="tooShort || !! serverPassword"
                    @input="serverPassword = ''"
                />
            </div>
            <x-password-strength value="password" />
            <p x-show="serverPassword" x-cloak x-text="serverPassword" class="mt-2 text-sm text-red-600"></p>
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="mt-1">
                <x-password-input
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    model="confirmation"
                    invalid="!! mismatch"
                    @input="serverPassword = ''"
                />
            </div>
            <p x-show="mismatch" x-cloak class="mt-2 text-sm text-red-600">Doesn't match yet.</p>
            <p x-show="matches" x-cloak class="mt-2 flex items-center gap-1 text-sm text-gcash-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                Passwords match
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button x-bind:disabled="!! blockedReason || submitting" class="disabled:opacity-40 disabled:cursor-not-allowed">{{ __('Save') }}</x-primary-button>

            <p x-show="showReason" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <script>
        function updatePasswordForm(config) {
            const MIN = 8;

            return {
                current: '',
                password: '',
                confirmation: '',
                serverCurrent: config.serverCurrent || '',
                serverPassword: config.serverPassword || '',
                submitting: false,

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
                    if (! this.current) return 'Enter your current password.';
                    if (! this.password) return 'Enter a new password.';
                    if (this.password.length < MIN) return 'Use at least ' + MIN + ' characters.';
                    if (! this.confirmation) return 'Type the new password again.';
                    if (this.mismatch) return "The two passwords don't match yet.";

                    return '';
                },

                get showReason() {
                    return !! this.blockedReason && ! this.tooShort && ! this.mismatch;
                },
            };
        }
    </script>
</section>
