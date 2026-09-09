<x-app-layout>

    <div x-data="{ userModalOpen: false, userMode: 'create', userId: null }">

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Header with Add User Button --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                <p class="text-sm text-gray-500 mt-1">Manage user accounts and permissions</p>
            </div>
            <button @click="userModalOpen = true; userMode = 'create'"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add User
            </button>
        </div>

        {{-- Search and Filters --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Search Users</label>
                    <div class="relative">
                        <input type="text" placeholder="Search by name or email..."
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Filter by Role</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>All Roles</option>
                        <option>Super Admin</option>
                        <option>Admin</option>
                        <option>Cashier</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Users Table --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        {{-- Users will be loaded from backend --}}
                    </tbody>
                </table>
            </div>

            {{-- Empty State --}}
            <div class="p-12 text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <p class="text-sm font-medium">No users found</p>
                <p class="text-xs mt-1">Try adjusting your search or filters</p>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-gray-50 px-5 py-6 pb-24">

        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">User Management</h2>
            <p class="text-xs text-gray-500 mt-1">Manage accounts and permissions</p>
        </div>

        {{-- Add User Button --}}
        <button @click="userModalOpen = true; userMode = 'create'"
                class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition active:scale-95 mb-5">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add New User
        </button>

        {{-- Search and Filter --}}
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-5">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Search</label>
                    <div class="relative">
                        <input type="text" placeholder="Name or email..."
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Role</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>All Roles</option>
                        <option>Super Admin</option>
                        <option>Admin</option>
                        <option>Cashier</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- User Cards --}}
        <div class="space-y-3">
            {{-- Users will be loaded from backend --}}

            {{-- Empty State --}}
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <p class="text-sm font-medium">No users found</p>
            </div>
        </div>
    </div>

    {{-- ===== Add/Edit User Modal ===== --}}
    <div x-show="userModalOpen"
         x-cloak
         @keydown.escape.window="userModalOpen = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">

        {{-- Overlay --}}
        <div x-show="userModalOpen" x-transition.opacity @click="userModalOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Dialog --}}
        <div x-show="userModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" x-text="userMode === 'create' ? 'Add New User' : 'Edit User'"></h3>
                <button @click="userModalOpen = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" placeholder="Enter full name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" placeholder="user@example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div x-show="userMode === 'create'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" placeholder="••••••••"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>Cashier</option>
                        <option>Admin</option>
                        <option>Super Admin</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="userModalOpen = false"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition-all duration-200 active:scale-95">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition-all duration-200 active:scale-95"
                            x-text="userMode === 'create' ? 'Add User' : 'Save Changes'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>

</x-app-layout>
