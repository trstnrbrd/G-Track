<x-app-layout>

    @php
        $rangeOptions = ['week' => 'This Week', 'month' => 'This Month', 'last30' => 'Last 30 Days', 'all' => 'All Time'];

        // Net change = what the day actually added to the till across both
        // balances. For a well-recorded day this equals the service charges
        // earned; a gap means something was mis-entered.
        $netChange = function ($session) {
            if ($session->closing_cash_balance === null) {
                return null;
            }

            return ((float) $session->closing_cash_balance + (float) $session->closing_gcash_balance)
                 - ((float) $session->opening_cash_balance + (float) $session->opening_gcash_balance);
        };
    @endphp

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Filters --}}
        <form method="GET" action="{{ route('balance') }}" class="bg-surface rounded-2xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Date Range</label>
                    <select name="range" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 flex items-end justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>

        {{-- Daily Balance Table --}}
        <div class="bg-surface rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-gray-800 text-lg font-bold">Daily Balance Summary</h2>
                    <p class="text-sm text-gray-500 mt-1">Opening and closing balances per day</p>
                </div>
                <a href="{{ route('export.sessions', request()->query()) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-surface border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Export PDF
                </a>
            </div>

            @if ($sessions->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/>
                    </svg>
                    <p class="text-sm font-medium">No balance records in this range</p>
                    <p class="text-xs mt-1">Start your first day to see daily balance summaries</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" rowspan="2">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" rowspan="2">Session</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-l border-gray-200" colspan="2">Cash</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-l border-gray-200" colspan="2">GCash</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider border-l border-gray-200" rowspan="2">Earned</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider" rowspan="2">Txns</th>
                            </tr>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 border-l border-gray-200">Opening</th>
                                <th class="px-6 py-2 text-right text-xs font-medium text-gray-500">Closing</th>
                                <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 border-l border-gray-200">Opening</th>
                                <th class="px-6 py-2 text-right text-xs font-medium text-gray-500">Closing</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($sessions as $session)
                                @php $stats = $totals->get($session->id); @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-900">{{ $session->started_at->format('M j, Y') }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ $session->started_at->format('g:i A') }}
                                            @if ($session->ended_at) &ndash; {{ $session->ended_at->format('g:i A') }} @endif
                                            @if ($session->duration())
                                                <span class="text-gray-300">&middot;</span> {{ $session->duration() }}
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($session->isActive())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                            </span>
                                        @elseif ($session->auto_closed)
                                            <span title="Closed automatically — these balances were never counted by hand"
                                                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                                Auto-closed
                                            </span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right tabular-nums border-l border-gray-100">₱{{ number_format($session->opening_cash_balance, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium text-right tabular-nums">
                                        {{ $session->closing_cash_balance === null ? '—' : '₱'.number_format($session->closing_cash_balance, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right tabular-nums border-l border-gray-100">₱{{ number_format($session->opening_gcash_balance, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium text-right tabular-nums">
                                        {{ $session->closing_gcash_balance === null ? '—' : '₱'.number_format($session->closing_gcash_balance, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-600 text-right tabular-nums border-l border-gray-100">
                                        ₱{{ number_format($stats->earnings ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right tabular-nums">{{ $stats->txn_count ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($sessions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">{{ $sessions->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-5 py-6 pb-safe-nav">

        {{-- Filters --}}
        <form method="GET" action="{{ route('balance') }}" class="bg-surface rounded-2xl shadow-sm p-4 mb-5 space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Date Range</label>
                <select name="range" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach ($rangeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                Apply Filter
            </button>
        </form>

        <h2 class="text-gray-800 text-base font-bold mb-3">Daily Balance Summary</h2>

        @forelse ($sessions as $session)
            @php
                $stats = $totals->get($session->id);
                $net = $netChange($session);
            @endphp
            <div class="bg-surface rounded-2xl shadow-sm p-4 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900">{{ $session->started_at->format('M j, Y') }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $session->started_at->format('g:i A') }}
                            @if ($session->ended_at) &ndash; {{ $session->ended_at->format('g:i A') }} @endif
                        </p>
                        @if ($session->duration())
                            <p class="text-[11px] text-gray-400 mt-0.5">Ran for {{ $session->duration() }}</p>
                        @endif
                    </div>
                    @if ($session->isActive())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                        </span>
                    @else
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Closed</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-gray-400 font-medium mb-1">Cash</p>
                        <p class="text-gray-600">Open <span class="float-right tabular-nums">₱{{ number_format($session->opening_cash_balance, 2) }}</span></p>
                        <p class="text-gray-900 font-semibold">Close <span class="float-right tabular-nums">{{ $session->closing_cash_balance === null ? '—' : '₱'.number_format($session->closing_cash_balance, 2) }}</span></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-gray-400 font-medium mb-1">GCash</p>
                        <p class="text-gray-600">Open <span class="float-right tabular-nums">₱{{ number_format($session->opening_gcash_balance, 2) }}</span></p>
                        <p class="text-gray-900 font-semibold">Close <span class="float-right tabular-nums">{{ $session->closing_gcash_balance === null ? '—' : '₱'.number_format($session->closing_gcash_balance, 2) }}</span></p>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 text-xs">
                    <span class="text-gray-500">{{ $stats->txn_count ?? 0 }} {{ Str::plural('transaction', $stats->txn_count ?? 0) }}</span>
                    <span class="font-bold text-amber-600">Earned ₱{{ number_format($stats->earnings ?? 0, 2) }}</span>
                </div>
            </div>
        @empty
            <div class="bg-surface rounded-2xl shadow-sm p-8 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/>
                </svg>
                <p class="text-sm font-medium">No balance records in this range</p>
            </div>
        @endforelse

        @if ($sessions->hasPages())
            <div class="mt-4">{{ $sessions->links() }}</div>
        @endif
    </div>

</x-app-layout>
