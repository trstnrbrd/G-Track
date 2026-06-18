<x-app-layout>

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Date Range</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>Today</option>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Transaction Type</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>All Types</option>
                        <option>Cash In</option>
                        <option>Cash Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" placeholder="Mobile number or reference..."
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-end">
                    <button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-medium">Total Cash In</p>
                        <p class="text-gray-900 text-xl font-bold">₱0.00</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-medium">Total Cash Out</p>
                        <p class="text-gray-900 text-xl font-bold">₱0.00</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-medium">Service Charges</p>
                        <p class="text-gray-900 text-xl font-bold">₱0.00</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-medium">Transactions</p>
                        <p class="text-gray-900 text-xl font-bold">0</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-gray-800 text-lg font-bold">Transaction History</h2>
                    <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        {{-- Transactions will be loaded from backend --}}
                    </tbody>
                </table>
            </div>

            {{-- Empty State --}}
            <div class="p-12 text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium">No more transactions to show</p>
                <p class="text-xs mt-1">Try adjusting your filters</p>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-gray-50 px-5 py-6 pb-24">

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-5">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date Range</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>Today</option>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Transaction Type</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>All Types</option>
                        <option>Cash In</option>
                        <option>Cash Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Search</label>
                    <div class="relative">
                        <input type="text" placeholder="Mobile number..."
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                </div>
                <button class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                    Apply Filters
                </button>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">Cash In</p>
                </div>
                <p class="text-gray-900 text-lg font-bold">₱0.00</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">Cash Out</p>
                </div>
                <p class="text-gray-900 text-lg font-bold">₱0.00</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">Fees</p>
                </div>
                <p class="text-gray-900 text-lg font-bold">₱0.00</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">Count</p>
                </div>
                <p class="text-gray-900 text-lg font-bold">0</p>
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-gray-800 text-base font-bold">Transactions</h2>
                <button class="text-blue-600 text-xs font-semibold">
                    <svg class="w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                </button>
            </div>

            {{-- Transaction Items --}}
            <div class="space-y-3">
                {{-- Transactions will be loaded from backend --}}
            </div>

            {{-- Empty State --}}
            <div class="text-center py-8 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs">No more transactions</p>
            </div>
        </div>
    </div>

</x-app-layout>
