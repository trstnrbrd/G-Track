<x-app-layout>

    {{-- Store items paid via GCash. The tindahan's own balance is out of scope:
         this page only records the sale and the cash moved to the tindahan. --}}

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        <div class="flex items-start justify-between gap-6 mb-6">
            <div>
                <h2 class="text-gray-900 text-lg font-bold">Items paid via GCash</h2>
                <p class="text-sm text-gray-600 mt-1 max-w-xl">
                    When a customer pays for a store item with GCash, record it here. The money lands in the wallet,
                    so the same amount of cash moves from the GCash drawer to the tindahan.
                </p>
            </div>
            <div class="text-right shrink-0">
                <button type="button" onclick="gtrackOpenStoreSale()" @disabled(! $session)
                        class="px-4 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    Item paid via GCash
                </button>
                @unless ($session)
                    <p class="mt-1.5 text-xs text-gray-600">Start the day on the Dashboard first.</p>
                @endunless
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Moved to tindahan today</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['moved_today'], 2) }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ $summary['count_today'] }} {{ Str::plural('sale', $summary['count_today']) }} this session</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Cash on hand (GCash drawer)</p>
                {{-- Neutral number + green dot: green means cash, and the dot stays legible in the dark theme. --}}
                <p class="mt-1 flex items-center gap-2 text-2xl font-bold text-gray-900 tabular-nums">
                    <span class="w-2 h-2 rounded-full bg-cash-500 shrink-0" aria-hidden="true"></span>₱{{ number_format((float) $balance->cash_balance, 2) }}
                </p>
                <p class="mt-0.5 text-xs text-gray-500">The most you can move right now</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Moved to tindahan, all time</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['moved_all_time'], 2) }}</p>
            </div>
        </div>

        <div class="bg-surface rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-gray-800 text-base font-bold">Store sales</h2>
            </div>

            @if ($sales->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-sm font-medium text-gray-900">No store sales yet</p>
                    <p class="mt-1 text-sm text-gray-500">Items paid for with GCash will show up here.</p>
                </div>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date &amp; Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900">{{ $sale->created_at->format('M j, Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $sale->created_at->format('g:i A') }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $sale->note ?: '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $sale->reference_number ?: '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right tabular-nums">₱{{ number_format($sale->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sale->user?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($sales->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">{{ $sales->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-5 py-6 pb-safe-nav">

        <p class="text-sm text-gray-600 mb-4">
            Customer paid for an item with GCash? Record it here — the same amount of cash moves from the GCash drawer to the tindahan.
        </p>

        <button type="button" onclick="gtrackOpenStoreSale()" @disabled(! $session)
                class="w-full py-3 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
            Item paid via GCash
        </button>
        @unless ($session)
            <p class="mt-1.5 text-xs text-gray-600 text-center">Start the day on the Dashboard first.</p>
        @endunless

        <div class="grid grid-cols-2 gap-3 mt-5">
            <div class="rounded-xl border border-gray-200 bg-surface p-4">
                <p class="text-xs text-gray-500">Moved to tindahan today</p>
                <p class="mt-1 text-lg font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['moved_today'], 2) }}</p>
                <p class="text-[11px] text-gray-500">{{ $summary['count_today'] }} {{ Str::plural('sale', $summary['count_today']) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-surface p-4">
                <p class="text-xs text-gray-500">Cash on hand</p>
                <p class="mt-1 flex items-center gap-2 text-lg font-bold text-gray-900 tabular-nums">
                    <span class="w-2 h-2 rounded-full bg-cash-500 shrink-0" aria-hidden="true"></span>₱{{ number_format((float) $balance->cash_balance, 2) }}
                </p>
                <p class="text-[11px] text-gray-500">Most you can move</p>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-600">All time: ₱{{ number_format($summary['moved_all_time'], 2) }} moved to the tindahan</p>

        <h2 class="text-gray-800 text-base font-bold mt-6 mb-3">Store sales</h2>

        @forelse ($sales as $sale)
            <div class="bg-surface rounded-2xl border border-gray-200 p-4 mb-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-900 truncate">{{ $sale->note ?: 'Store item' }}</p>
                        <p class="text-xs text-gray-500 font-mono truncate">Ref {{ $sale->reference_number ?: 'N/A' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-base font-bold text-gray-900 tabular-nums">₱{{ number_format($sale->amount, 2) }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $sale->created_at->format('M j, g:i A') }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-surface rounded-2xl border border-gray-200 p-8 text-center">
                <p class="text-sm font-medium text-gray-900">No store sales yet</p>
                <p class="mt-1 text-xs text-gray-500">Items paid for with GCash will show up here.</p>
            </div>
        @endforelse

        @if ($sales->hasPages())
            <div class="mt-4">{{ $sales->links() }}</div>
        @endif
    </div>

    @include('partials.store-sale-modal')

</x-app-layout>
