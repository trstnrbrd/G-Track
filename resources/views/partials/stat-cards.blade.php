{{-- The four totals — cash in, cash out, service charges, transaction count —
     as a row of cards with a tinted icon circle, a label, and a figure.

     Shared by Dashboard and History so the two pages can never drift apart:
     labels, icons and colors all live here and nowhere else.

     Expects $totals = [
         'cash_in' => float, 'cash_out' => float,
         'service_charge' => float, 'count' => int,
     ]
     Pass 'compact' => true for the 2-column mobile grid. --}}
@php
    $compact = $compact ?? false;

    // Green and blue use the brand tokens so these match the balance cards.
    $cards = [
        ['Total Cash In', '₱'.number_format($totals['cash_in'], 2), 'bg-cash-100 text-cash-600',
            'M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75'],
        ['Total Cash Out', '₱'.number_format($totals['cash_out'], 2), 'bg-gcash-100 text-gcash-600',
            'M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75'],
        ['Service Charges', '₱'.number_format($totals['service_charge'], 2), 'bg-amber-100 text-amber-600',
            'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Transactions', number_format($totals['count']), 'bg-purple-100 text-purple-600',
            'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
@endphp

<div class="grid {{ $compact ? 'grid-cols-2 gap-3' : 'grid-cols-4 gap-4' }}">
    @foreach ($cards as [$label, $value, $tone, $icon])
        <div class="flex items-center gap-3 rounded-2xl bg-surface shadow-sm {{ $compact ? 'p-3' : 'p-4' }}">
            <div class="flex shrink-0 items-center justify-center rounded-full {{ $compact ? 'h-8 w-8' : 'h-9 w-9' }} {{ $tone }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-400">{{ $label }}</p>
                <p class="truncate font-bold tabular-nums text-gray-900 {{ $compact ? 'text-[15px]' : 'text-lg' }}">{{ $value }}</p>
            </div>
        </div>
    @endforeach
</div>
