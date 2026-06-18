<x-app-layout>

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Date Range</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>Last 30 Days</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <div class="col-span-2 flex items-end justify-end gap-2">
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                        Apply Filter
                    </button>
                    <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        {{-- Daily Balance Table --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-gray-800 text-lg font-bold">Daily Balance Summary</h2>
                <p class="text-sm text-gray-500 mt-1">Opening and closing balances per day</p>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider" colspan="2">Cash Balance</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider" colspan="2">GCash Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Net Change</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Transactions</th>
                        </tr>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-2"></th>
                            <th class="px-6 py-2"></th>
                            <th class="px-6 py-2 text-center text-xs font-medium text-gray-500">Opening</th>
                            <th class="px-6 py-2 text-center text-xs font-medium text-gray-500">Closing</th>
                            <th class="px-6 py-2 text-center text-xs font-medium text-gray-500">Opening</th>
                            <th class="px-6 py-2 text-center text-xs font-medium text-gray-500">Closing</th>
                            <th class="px-6 py-2"></th>
                            <th class="px-6 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        {{-- Daily balance records will be loaded from backend --}}
                    </tbody>
                </table>
            </div>

            {{-- Empty State --}}
            <div class="p-12 text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/>
                </svg>
                <p class="text-sm font-medium">No balance records yet</p>
                <p class="text-xs mt-1">Start your first day to see daily balance summaries</p>
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
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>Last 30 Days</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <button class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                    Apply Filter
                </button>
            </div>
        </div>

        {{-- Daily Balance Cards --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-gray-800 text-base font-bold">Daily Balance Summary</h2>
                <button class="text-blue-600 text-xs font-semibold">
                    <svg class="w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                </button>
            </div>

            {{-- Daily balance cards will be loaded from backend --}}

            {{-- Empty State --}}
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/>
                </svg>
                <p class="text-sm font-medium">No balance records yet</p>
                <p class="text-xs mt-1">Start your first day to see daily summaries</p>
            </div>
        </div>
    </div>

</x-app-layout>
