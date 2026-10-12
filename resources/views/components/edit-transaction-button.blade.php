@props(['transaction', 'label' => false, 'compact' => false])

{{-- Opens the edit modal for one recorded transaction.

     The modal itself is partials/edit-transaction-modal and must be included on
     the page. This is a plain onclick rather than an Alpine @click so it works
     inside table rows and lists that sit outside any x-data scope. --}}
<button type="button"
        onclick="gtrackEditTransaction(@js($transaction->editPayload()))"
        aria-label="Edit mobile number and reference"
        {{ $attributes->class([
            'inline-flex items-center gap-1.5 rounded-lg transition',
            'px-3 py-2 text-gcash-600 hover:bg-gcash-50' => $label,
            'p-2 text-gray-500 hover:text-gcash-600 hover:bg-gray-50' => ! $label && ! $compact,
            // Tighter, for a card where every pixel of height counts (the phone list).
            'p-1.5 text-gray-500 hover:text-gcash-600 hover:bg-gray-50' => ! $label && $compact,
        ]) }}>
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
    </svg>
    @if ($label)
        <span class="text-xs font-semibold">Edit number / reference</span>
    @endif
</button>
