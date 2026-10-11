<x-app-layout>

    {{-- Owner-only (routes use the `owner` middleware). The owner creates
         accounts and sets passwords; there is no public registration and
         staff may not have a real inbox, so no email is involved. --}}

    @php
        $roleLabels = ['super_admin' => 'Owner', 'admin' => 'Staff'];
        $roleOptions = ['all' => 'All roles', 'super_admin' => 'Owner', 'admin' => 'Staff'];

        // A failed save reloads the page: reopen the modal that was in use.
        $reopenCreate = $errors->createUser->any();
        $failedReset = $errors->resetPassword->any()
            ? \App\Models\User::find(old('user_id'))
            : null;
    @endphp

    <div x-data="userManagement({
            createOpen: @js($reopenCreate),
            reset: @js($failedReset ? ['id' => $failedReset->id, 'name' => $failedReset->name, 'url' => route('user-management.password', $failedReset, false)] : null),
         })">

        {{-- ============================================================ --}}
        {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
        {{-- ============================================================ --}}
        <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-gray-900 text-lg font-bold">User Management</h2>
                    <p class="text-sm text-gray-600 mt-1">Create accounts and reset passwords. {{ $total }} {{ Str::plural('account', $total) }}.</p>
                </div>
                <button type="button" @click="openCreate()"
                        class="px-4 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95">
                    Add user
                </button>
            </div>

            <form method="GET" action="{{ route('user-management') }}" class="bg-surface rounded-2xl border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-4 gap-4 items-end">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name or email..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gcash-600 hover:bg-gcash-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">Apply</button>
                        <a href="{{ route('user-management') }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-surface border border-gray-300 rounded-lg hover:bg-gray-50 transition">Reset</a>
                    </div>
                </div>
            </form>

            <div class="bg-surface rounded-2xl border border-gray-200 overflow-hidden">
                @if ($users->isEmpty())
                    <div class="p-12 text-center">
                        <p class="text-sm font-medium text-gray-900">No users found</p>
                        <p class="mt-1 text-sm text-gray-500">Try a different search or role.</p>
                    </div>
                @else
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Password</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))<span class="ml-1 text-xs font-normal text-gray-500">(you)</span>@endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right">
                                        @unless ($user->is(auth()->user()))
                                            <button type="button"
                                                    @click="openReset(@js(['id' => $user->id, 'name' => $user->name, 'url' => route('user-management.password', $user, false)]))"
                                                    class="px-3 py-1.5 text-xs font-semibold text-gcash-600 rounded-lg hover:bg-gcash-50 transition">Reset password</button>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($users->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">{{ $users->links() }}</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
        {{-- ============================================================ --}}
        <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-5 py-6 pb-safe-nav">

            <button type="button" @click="openCreate()"
                    class="w-full py-3 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95 mb-5">
                Add user
            </button>

            <form method="GET" action="{{ route('user-management') }}" class="bg-surface rounded-2xl border border-gray-200 p-4 mb-5 space-y-3">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name or email..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500">
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500">
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full px-4 py-2.5 bg-gcash-600 hover:bg-gcash-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">Apply</button>
            </form>

            @forelse ($users as $user)
                <div class="bg-surface rounded-2xl border border-gray-200 p-4 mb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">
                                {{ $user->name }}
                                @if ($user->is(auth()->user()))<span class="text-xs font-normal text-gray-500">(you)</span>@endif
                            </p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                    </div>
                    @unless ($user->is(auth()->user()))
                        <div class="mt-3 pt-2 border-t border-gray-100 flex justify-end">
                            <button type="button"
                                    @click="openReset(@js(['id' => $user->id, 'name' => $user->name, 'url' => route('user-management.password', $user, false)]))"
                                    class="px-3 py-2 text-xs font-semibold text-gcash-600 rounded-lg hover:bg-gcash-50 transition">Reset password</button>
                        </div>
                    @endunless
                </div>
            @empty
                <div class="bg-surface rounded-2xl border border-gray-200 p-8 text-center">
                    <p class="text-sm font-medium text-gray-900">No users found</p>
                    <p class="mt-1 text-xs text-gray-500">Try a different search or role.</p>
                </div>
            @endforelse

            @if ($users->hasPages())
                <div class="mt-4">{{ $users->links() }}</div>
            @endif
        </div>

        {{-- ===== Add user ===== --}}
        <div x-show="createOpen" x-cloak @keydown.escape.window="createOpen = false"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div x-show="createOpen" x-transition.opacity @click="createOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="createOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-surface rounded-2xl shadow-2xl w-full max-w-md max-h-[92vh] overflow-y-auto p-6">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Add user</h3>
                    <button type="button" @click="createOpen = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('user-management.store') }}" class="space-y-4" @submit="submitting = true">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ $reopenCreate ? old('name') : '' }}" required maxlength="255" autocomplete="off"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 {{ $errors->createUser->has('name') ? 'border-red-400' : 'border-gray-300' }}">
                        @if ($errors->createUser->has('name'))<p class="mt-1 text-xs text-red-600">{{ $errors->createUser->first('name') }}</p>@endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ $reopenCreate ? old('email') : '' }}" required maxlength="255" autocomplete="off"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 {{ $errors->createUser->has('email') ? 'border-red-400' : 'border-gray-300' }}">
                        <p class="mt-1 text-xs text-gray-500">This is the login. It doesn't have to be a real inbox.</p>
                        @if ($errors->createUser->has('email'))<p class="mt-1 text-xs text-red-600">{{ $errors->createUser->first('email') }}</p>@endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500">
                            <option value="admin" @selected(old('role', 'admin') === 'admin')>Staff — counter only</option>
                            <option value="super_admin" @selected(old('role') === 'super_admin')>Owner — also Capital and User Management</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" minlength="8"
                                class="w-full px-3 py-2 pr-16 border rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 {{ $errors->createUser->has('password') ? 'border-red-400' : 'border-gray-300' }}">
                            <button type="button" @click="showPassword = ! showPassword"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-xs font-semibold text-gcash-600" x-text="showPassword ? 'Hide' : 'Show'"></button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">At least 8 characters. Tell it to them in person; they can change it in Settings.</p>
                        @if ($errors->createUser->has('password'))<p class="mt-1 text-xs text-red-600">{{ $errors->createUser->first('password') }}</p>@endif
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="createOpen = false"
                                class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition active:scale-95">Cancel</button>
                        <button type="submit" :disabled="submitting"
                                class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-text="submitting ? 'Creating…' : 'Create account'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== Reset password ===== --}}
        <div x-show="resetOpen" x-cloak @keydown.escape.window="resetOpen = false"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div x-show="resetOpen" x-transition.opacity @click="resetOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="resetOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-surface rounded-2xl shadow-2xl w-full max-w-md p-6">

                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xl font-bold text-gray-900">Reset password</h3>
                    <button type="button" @click="resetOpen = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">New password for <span class="font-semibold" x-text="resetName"></span>. They'll be signed out everywhere.</p>

                <form method="POST" :action="resetUrl" class="space-y-4" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="user_id" :value="resetId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-ref="resetPassword" required autocomplete="new-password" minlength="8"
                                class="w-full px-3 py-2 pr-16 border rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 {{ $errors->resetPassword->has('password') ? 'border-red-400' : 'border-gray-300' }}">
                            <button type="button" @click="showPassword = ! showPassword"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-xs font-semibold text-gcash-600" x-text="showPassword ? 'Hide' : 'Show'"></button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">At least 8 characters.</p>
                        @if ($errors->resetPassword->has('password'))<p class="mt-1 text-xs text-red-600">{{ $errors->resetPassword->first('password') }}</p>@endif
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="resetOpen = false"
                                class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition active:scale-95">Cancel</button>
                        <button type="submit" :disabled="submitting"
                                class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-text="submitting ? 'Saving…' : 'Set password'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function userManagement(config) {
            return {
                createOpen: config.createOpen,
                resetOpen: !! config.reset,
                submitting: false,
                showPassword: false,

                resetId: config.reset ? config.reset.id : null,
                resetName: config.reset ? config.reset.name : '',
                resetUrl: config.reset ? config.reset.url : '',

                openCreate() {
                    this.submitting = false;
                    this.showPassword = false;
                    this.createOpen = true;
                },

                openReset(user) {
                    this.resetId = user.id;
                    this.resetName = user.name;
                    this.resetUrl = user.url;
                    this.submitting = false;
                    this.showPassword = false;
                    this.resetOpen = true;
                    this.$nextTick(() => this.$refs.resetPassword.focus());
                },
            };
        }
    </script>

</x-app-layout>
