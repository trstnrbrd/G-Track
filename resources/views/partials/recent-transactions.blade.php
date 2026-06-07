{{-- Renders the recent transactions list, or an empty state.
     Expects $recent (Collection of Transaction). Lives inside a padded white card. --}}
@forelse ($recent as $txn)
    <div class="flex items-center justify-between py-3.5 border-b border-gray-50 last:border-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 {{ $txn->isCashIn() ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    @if ($txn->isCashIn())
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75"/>
                    @endif
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-800">{{ $txn->isCashIn() ? 'Cash In' : 'Cash Out' }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $txn->mobile_number ?: '—' }} · Ref {{ $txn->reference_number ?: 'N/A' }}</p>
            </div>
        </div>
        <div class="text-right shrink-0 ml-3">
            <p class="text-sm font-bold text-gray-900">₱{{ number_format($txn->amount, 2) }}</p>
            <p class="text-xs text-gray-400">{{ $txn->created_at->format('g:i A') }}</p>
        </div>
    </div>
@empty
    <div class="py-12 flex flex-col items-center text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-3">
            <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
        </div>
        <p class="text-gray-400 text-sm font-medium">No transactions yet.</p>
        <p class="text-gray-300 text-xs mt-1">Transactions you log will appear here.</p>
    </div>
@endforelse
