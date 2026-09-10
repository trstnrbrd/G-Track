<x-app-layout>

    @php
        $rangeOptions = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time', 'custom' => 'Custom Range'];
        $typeOptions = ['all' => 'All Types', 'cash_in' => 'Cash In', 'cash_out' => 'Cash Out'];
    @endphp

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Filters --}}
        <form method="GET" action="{{ route('history') }}" x-data="{ range: '{{ $filters['range'] }}' }"
              class="bg-surface rounded-2xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Date Range</label>
                    <select name="range" x-model="range" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Transaction Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Mobile number or reference..."
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                        Apply Filters
                    </button>
                    <a href="{{ route('history') }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-surface border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Reset
                    </a>
                </div>
            </div>

            {{-- Custom range inputs, only relevant when "Custom Range" is picked --}}
            <div x-show="range === 'custom'" x-cloak class="grid grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            @error('to')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </form>

        {{-- Summary Stats (computed over every matching row, not just this page).
             Same component as the Dashboard. --}}
        <div class="mb-6">
            @include('partials.stat-cards', ['totals' => $summary])
        </div>

        {{-- Transactions Table --}}
        <div class="bg-surface rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-gray-800 text-lg font-bold">Transaction History</h2>
                    <p class="text-sm text-gray-500">{{ $transactions->total() }} {{ Str::plural('transaction', $transactions->total()) }}</p>
                </div>
                {{-- PDF of whatever the filters currently show, not just this page. --}}
                <a href="{{ route('export.transactions', request()->query()) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-surface border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Export PDF
                </a>
            </div>

            @if ($transactions->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">No transactions found</p>
                    <p class="text-xs mt-1">Try widening the date range or clearing the search.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date &amp; Time</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Fee</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($transactions as $txn)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-900">{{ $txn->created_at->format('M j, Y') }}</p>
                                        <p class="text-xs text-gray-400">{{ $txn->created_at->format('g:i A') }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $txn->isCashIn() ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $txn->isCashIn() ? 'Cash In' : 'Cash Out' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">{{ $txn->mobile_number ?: '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right tabular-nums">₱{{ number_format($txn->amount, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm text-gray-700 tabular-nums">₱{{ number_format($txn->service_charge, 2) }}</span>
                                        <span class="block text-[11px] text-gray-400">via {{ $txn->charge_paid_in === 'gcash' ? 'GCash' : 'cash' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $txn->reference_number ?: '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $txn->user?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">{{ $transactions->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-5 py-6 pb-safe-nav">

        {{-- Filters --}}
        <form method="GET" action="{{ route('history') }}" class="bg-surface rounded-2xl shadow-sm p-4 mb-5 space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Date Range</label>
                    <select name="range" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($rangeOptions as $value => $label)
                            @continue($value === 'custom')
                            <option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Mobile number or reference..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                Apply Filters
            </button>
        </form>

        {{-- Summary — same component as the Dashboard --}}
        <div class="mb-5">
            @include('partials.stat-cards', ['totals' => $summary, 'compact' => true])
        </div>

        {{-- Transaction cards --}}
        <h2 class="text-gray-800 text-base font-bold mb-3">Transaction History</h2>

        @forelse ($transactions as $txn)
            <div class="bg-surface rounded-2xl shadow-sm p-4 mb-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $txn->isCashIn() ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $txn->isCashIn() ? 'Cash In' : 'Cash Out' }}
                        </span>
                        <p class="text-sm text-gray-700 mt-1.5 truncate font-mono">{{ $txn->mobile_number ?: '—' }}</p>
                        <p class="text-xs text-gray-400 font-mono truncate">Ref {{ $txn->reference_number ?: 'N/A' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-base font-bold text-gray-900 tabular-nums">₱{{ number_format($txn->amount, 2) }}</p>
                        <p class="text-xs text-amber-600 font-medium">+₱{{ number_format($txn->service_charge, 2) }} fee</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $txn->created_at->format('M j, g:i A') }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-surface rounded-2xl shadow-sm p-8 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium">No transactions found</p>
                <p class="text-xs mt-1">Try widening the date range.</p>
            </div>
        @endforelse

        @if ($transactions->hasPages())
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </div>

</x-app-layout>
