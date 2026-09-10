@extends('exports.layout')

{{-- Daily Balance Summary PDF. Expects $rows (capped at $maxRows) and $totals,
     which covers every day in the range even when the table is capped. --}}

@section('content')
    <table class="summary">
        <tr>
            <td class="gcash">
                <div class="label">Days recorded</div>
                <div class="figure">{{ number_format($totals['days']) }}</div>
            </td>
            <td class="purple">
                <div class="label">Transactions</div>
                <div class="figure">{{ number_format($totals['transactions']) }}</div>
            </td>
            <td class="amber">
                <div class="label">Total earned</div>
                <div class="figure">₱{{ number_format($totals['earned'], 2) }}</div>
            </td>
        </tr>
    </table>

    @if ($rows->isEmpty())
        <p class="empty">No days recorded in this range.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 10%">Date</th>
                    <th style="width: 8%">Opened</th>
                    <th style="width: 8%">Closed</th>
                    <th style="width: 8%">Duration</th>
                    <th style="width: 9%">Status</th>
                    <th class="right" style="width: 10%">Cash open</th>
                    <th class="right" style="width: 10%">Cash close</th>
                    <th class="right" style="width: 10%">GCash open</th>
                    <th class="right" style="width: 10%">GCash close</th>
                    <th class="right" style="width: 7%">Txns</th>
                    <th class="right" style="width: 10%">Earned</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $session)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="nowrap">{{ $session->started_at->format('M j, Y') }}</td>
                        <td class="nowrap">{{ $session->started_at->format('g:i A') }}</td>
                        <td class="nowrap">{{ $session->ended_at?->format('g:i A') ?? '—' }}</td>
                        <td class="muted">{{ $session->duration() ?? '—' }}</td>
                        <td>
                            @if ($session->isActive())
                                <span class="pill active">Active</span>
                            @elseif ($session->auto_closed)
                                <span class="pill auto">Auto-closed*</span>
                            @else
                                <span class="pill closed">Closed</span>
                            @endif
                        </td>
                        <td class="right">₱{{ number_format($session->opening_cash_balance, 2) }}</td>
                        <td class="right">{{ $session->closing_cash_balance === null ? '—' : '₱'.number_format($session->closing_cash_balance, 2) }}</td>
                        <td class="right">₱{{ number_format($session->opening_gcash_balance, 2) }}</td>
                        <td class="right">{{ $session->closing_gcash_balance === null ? '—' : '₱'.number_format($session->closing_gcash_balance, 2) }}</td>
                        <td class="right">{{ number_format($session->transactions_count) }}</td>
                        <td class="right"><strong>₱{{ number_format((float) $session->transactions_sum_service_charge, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- An auto-closed day was never counted by hand, so its closing
             balances are what the app believed, not a verified count. A printed
             report must not let that pass as a normal close. --}}
        @if ($rows->contains('auto_closed', true))
            <p class="muted" style="margin-top: 3mm; font-size: 7.5pt;">
                * Auto-closed: nobody pressed End Day, so the system closed it. Those closing balances were never counted by hand.
            </p>
        @endif

        @if ($totals['days'] > $rows->count())
            <div class="note">
                Showing the first {{ number_format($rows->count()) }} of {{ number_format($totals['days']) }} days.
                The totals above include all of them — pick a shorter range on the Balance page to list the rest.
            </div>
        @endif
    @endif
@endsection
