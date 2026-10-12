<x-app-layout>

    @php
        $kindLabels = [
            'borrow' => 'Borrow',
            'repay' => 'Repay',
            'capital' => 'Add capital',
        ];
        $balanceLabels = ['cash' => 'Cash (blue box)', 'gcash' => 'GCash wallet'];
        $gap = round($summary['actual'] - $summary['expected'], 2);
    @endphp

    {{-- Capital (puhunan) − debt (utang) = what should be in the GCash business.
         The store's own money is out of scope; this page only follows the GCash capital. --}}

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        <div class="flex items-start justify-between gap-6 mb-6">
            <div class="max-w-xl">
                <h2 class="text-gray-900 text-lg font-bold">Capital and debt</h2>
                <p class="text-sm text-gray-600 mt-1">
                    When money is taken out of the GCash capital (groceries, bills), record it here. When it's paid back, record that too.
                    <span class="font-semibold">Capital − debt</span> is what should be in GCash.
                </p>
                <p class="text-xs text-gray-500 mt-1">Puhunan at utang: kapag may kinuha o ibinalik, isulat dito.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="gtrackOpenCapital('borrow')"
                        class="px-4 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95">Borrow</button>
                <button type="button" onclick="gtrackOpenCapital('repay')"
                        class="px-4 py-2.5 rounded-xl font-semibold text-gray-700 bg-surface border border-gray-300 hover:bg-gray-50 transition active:scale-95">Repay</button>
                <button type="button" onclick="gtrackOpenCapital('capital')"
                        class="px-4 py-2.5 rounded-xl font-semibold text-gray-700 bg-surface border border-gray-300 hover:bg-gray-50 transition active:scale-95">Add capital</button>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-3">
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Capital <span class="text-xs">· puhunan</span></p>
                <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['puhunan'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Debt <span class="text-xs">· utang</span></p>
                <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['utang'], 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-surface p-5">
                <p class="text-sm text-gray-500">Should be in GCash</p>
                {{-- Neutral number + blue dot: blue means GCash, and the dot stays legible in the dark theme. --}}
                <p class="mt-1 flex items-center gap-2 text-2xl font-bold text-gray-900 tabular-nums">
                    <span class="w-2 h-2 rounded-full bg-gcash-600 shrink-0" aria-hidden="true"></span>{{ $summary['expected'] < 0 ? '−' : '' }}₱{{ number_format(abs($summary['expected']), 2) }}
                </p>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            Actual now (wallet + cash on hand): <span class="font-semibold tabular-nums">₱{{ number_format($summary['actual'], 2) }}</span>
            @if (abs($gap) >= 0.01 && $summary['puhunan'] > 0)
                &middot; <span class="tabular-nums">₱{{ number_format(abs($gap), 2) }}</span> {{ $gap > 0 ? 'over' : 'short' }}
            @endif
        </p>

        <div class="bg-surface rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-gray-800 text-base font-bold">Ledger</h2>
            </div>

            @if ($entries->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-sm font-medium text-gray-900">Nothing recorded yet</p>
                    <p class="mt-1 text-sm text-gray-500">Start with <span class="font-semibold">Add capital</span>: enter the current capital and choose "Nothing moves".</p>
                </div>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date &amp; Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Moved</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php($previousDay = null)
                        @foreach ($entries as $entry)
                            @if ($entry->created_at->toDateString() !== $previousDay)
                                <x-day-divider-row :date="$entry->created_at" :colspan="6" />
                                @php($previousDay = $entry->created_at->toDateString())
                            @endif
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900">{{ $entry->created_at->format('M j, Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $entry->created_at->format('g:i A') }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $kindLabels[$entry->kind] ?? $entry->kind }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ \App\Models\CapitalEntry::PURPOSES[$entry->purpose] ?? null }}
                                    @if ($entry->note)<span class="text-gray-500">{{ $entry->purpose ? ' · ' : '' }}{{ $entry->note }}</span>@endif
                                    @if (! $entry->purpose && ! $entry->note)—@endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $balanceLabels[$entry->balance] ?? 'Nothing moved' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right tabular-nums">₱{{ number_format($entry->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->user?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($entries->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">{{ $entries->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
     <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-3 py-3 pb-safe-nav">

        <div class="rounded-xl border border-gray-200 bg-surface p-3">
            <p class="text-xs text-gray-500">Should be in GCash</p>
            <p class="mt-1 flex items-center gap-2 text-2xl font-bold text-gray-900 tabular-nums">
                <span class="w-2 h-2 rounded-full bg-gcash-600 shrink-0" aria-hidden="true"></span>{{ $summary['expected'] < 0 ? '−' : '' }}₱{{ number_format(abs($summary['expected']), 2) }}
            </p>
            <p class="mt-1 text-xs text-gray-600">Capital − debt</p>

            <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-500">Capital</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['puhunan'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Debt</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums">₱{{ number_format($summary['utang'], 2) }}</p>
                </div>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-600">
            Actual now: <span class="font-semibold tabular-nums">₱{{ number_format($summary['actual'], 2) }}</span>
            @if (abs($gap) >= 0.01 && $summary['puhunan'] > 0)
                &middot; <span class="tabular-nums">₱{{ number_format(abs($gap), 2) }}</span> {{ $gap > 0 ? 'over' : 'short' }}
            @endif
        </p>

        <div class="grid grid-cols-2 gap-2 mt-3">
            <button type="button" onclick="gtrackOpenCapital('borrow')"
                    class="py-3 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 transition active:scale-95">Borrow</button>
            <button type="button" onclick="gtrackOpenCapital('repay')"
                    class="py-3 rounded-xl font-semibold text-gray-700 bg-surface border border-gray-300 hover:bg-gray-50 transition active:scale-95">Repay</button>
        </div>
        <button type="button" onclick="gtrackOpenCapital('capital')"
                class="mt-2 w-full py-3 rounded-xl font-semibold text-gray-700 bg-surface border border-gray-300 hover:bg-gray-50 transition active:scale-95">Add capital</button>

        <h2 class="text-gray-800 text-base font-bold mt-4 mb-2">Ledger</h2>

        @php($previousDay = null)
        @forelse ($entries as $entry)
            @if ($entry->created_at->toDateString() !== $previousDay)
                <x-day-divider :date="$entry->created_at" />
                @php($previousDay = $entry->created_at->toDateString())
            @endif
            <div class="bg-surface rounded-xl border border-gray-200 px-3 py-2 mb-1.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700">{{ $kindLabels[$entry->kind] ?? $entry->kind }}</span>
                        @php($what = \App\Models\CapitalEntry::PURPOSES[$entry->purpose] ?? $entry->note)
                        @if ($what)
                            <p class="text-sm text-gray-900 mt-1.5 truncate">{{ $what }}</p>
                        @endif
                        <p class="text-xs text-gray-500 truncate {{ $what ? '' : 'mt-1.5' }}">{{ $balanceLabels[$entry->balance] ?? 'Nothing moved' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-base font-bold text-gray-900 tabular-nums">₱{{ number_format($entry->amount, 2) }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $entry->created_at->format('g:i A') }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-surface rounded-2xl border border-gray-200 p-6 text-center">
                <p class="text-sm font-medium text-gray-900">Nothing recorded yet</p>
                <p class="mt-1 text-xs text-gray-500">Start with "Add capital": enter the current capital and choose "Nothing moves".</p>
            </div>
        @endforelse

        @if ($entries->hasPages())
            <div class="mt-4">{{ $entries->links() }}</div>
        @endif
    </div>

    @include('partials.capital-entry-modal')

</x-app-layout>
