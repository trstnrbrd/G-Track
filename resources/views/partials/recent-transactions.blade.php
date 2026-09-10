{{-- Recent transactions list, or an empty state that says what to do next.

     Expects $recent (Collection of Transaction) and optionally $sessionActive.
     Rows carry their own horizontal padding, so the container should have none.

     Direction is shown by the arrow, colored by the balance that RECEIVES money:
     green for a cash in (cash comes into the drawer), blue for a cash out (GCash
     comes into the wallet). Amounts are unsigned on purpose — a cash out lowers
     cash but raises GCash, so a single +/− sign would be wrong for one of them. --}}
@forelse ($recent as $txn)
    <div class="flex items-center justify-between gap-4 px-5 py-3 border-b border-gray-100 last:border-0">
        <div class="flex items-center gap-3 min-w-0">
            <svg class="w-[18px] h-[18px] shrink-0 {{ $txn->isCashIn() ? 'text-cash-600' : 'text-gcash-600' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                @if ($txn->isCashIn())
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                @endif
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900">{{ $txn->isCashIn() ? 'Cash in' : 'Cash out' }}</p>
                <p class="text-xs text-gray-500 truncate">
                    <span class="font-mono">{{ $txn->mobile_number ?: '—' }}</span>
                    @if ($txn->reference_number)
                        <span class="text-gray-300">·</span> <span class="font-mono">{{ $txn->reference_number }}</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="text-right shrink-0">
            <p class="text-sm font-semibold text-gray-900 tabular-nums">₱{{ number_format($txn->amount, 2) }}</p>
            <p class="text-xs text-gray-500 tabular-nums">{{ $txn->created_at->format('g:i A') }}</p>
        </div>
    </div>
@empty
    <div class="px-5 py-10 text-center">
        <p class="text-sm font-medium text-gray-900">No transactions yet</p>
        <p class="mt-1 text-sm text-gray-500">
            {{ ($sessionActive ?? false)
                ? 'Record a cash in or cash out and it will show up here.'
                : 'Start the day, then record your first transaction.' }}
        </p>
    </div>
@endforelse
