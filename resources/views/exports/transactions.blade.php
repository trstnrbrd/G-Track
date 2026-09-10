@extends('exports.layout')

{{-- Transaction History PDF. Expects $rows (capped at $maxRows) and $summary,
     which covers every matching transaction even when the table is capped. --}}

@section('content')
    <table class="summary">
        <tr>
            <td class="cash">
                <div class="label">Total cash in</div>
                <div class="figure">₱{{ number_format($summary['cash_in'], 2) }}</div>
            </td>
            <td class="gcash">
                <div class="label">Total cash out</div>
                <div class="figure">₱{{ number_format($summary['cash_out'], 2) }}</div>
            </td>
            <td class="amber">
                <div class="label">Service charges</div>
                <div class="figure">₱{{ number_format($summary['service_charge'], 2) }}</div>
            </td>
            <td class="purple">
                <div class="label">Transactions</div>
                <div class="figure">{{ number_format($summary['count']) }}</div>
            </td>
        </tr>
    </table>

    @if ($rows->isEmpty())
        <p class="empty">No transactions match these filters.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 11%">Date</th>
                    <th style="width: 9%">Time</th>
                    <th style="width: 9%">Type</th>
                    <th style="width: 12%">Customer</th>
                    <th style="width: 20%">Reference</th>
                    <th class="right" style="width: 11%">Amount</th>
                    <th class="right" style="width: 8%">Charge</th>
                    <th style="width: 7%">Paid in</th>
                    <th style="width: 13%">Recorded by</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $txn)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="nowrap">{{ $txn->created_at->format('M j, Y') }}</td>
                        <td class="muted nowrap">{{ $txn->created_at->format('g:i A') }}</td>
                        {{-- Colored by the balance that receives money, as in the app. --}}
                        <td><span class="dot {{ $txn->isCashIn() ? 'cash' : 'gcash' }}"></span>{{ $txn->isCashIn() ? 'Cash in' : 'Cash out' }}</td>
                        <td class="mono">{{ $txn->mobile_number ?: '—' }}</td>
                        <td class="mono wrap">{{ $txn->reference_number ?: '—' }}</td>
                        {{-- Unsigned on purpose: a cash out lowers cash but raises GCash. --}}
                        <td class="right nowrap"><strong>₱{{ number_format($txn->amount, 2) }}</strong></td>
                        <td class="right nowrap">₱{{ number_format($txn->service_charge, 2) }}</td>
                        <td>{{ $txn->charge_paid_in === 'gcash' ? 'GCash' : 'Cash' }}</td>
                        <td class="muted">{{ $txn->user?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($summary['count'] > $rows->count())
            <div class="note">
                Showing the first {{ number_format($rows->count()) }} of {{ number_format($summary['count']) }} transactions.
                The totals above include all of them — narrow the date range on the History page to list the rest.
            </div>
        @endif
    @endif
@endsection
