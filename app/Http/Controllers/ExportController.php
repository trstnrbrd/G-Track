<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Transaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * CSV exports for History and Balance.
     *
     * Streamed rather than built in memory: a shop running for a year will have
     * tens of thousands of transactions, and loading them all to build one
     * string would exhaust PHP's memory limit on the kind of machine this runs
     * on. `chunk()` keeps only 500 rows in memory at a time.
     */
    public function transactions(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'range' => ['nullable', 'in:today,week,month,all,custom'],
            'type' => ['nullable', 'in:all,cash_in,cash_out'],
            'search' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Transaction::query()->with('user');
        $this->applyTransactionFilters($query, $filters);

        return $this->stream('gtrack-transactions-'.now()->format('Y-m-d').'.csv', [
            'Date', 'Time', 'Type', 'Mobile Number', 'Amount',
            'Service Charge', 'Charge Paid In', 'Reference Number', 'Recorded By',
        ], function () use ($query) {
            foreach ($query->orderBy('created_at')->orderBy('id')->cursor() as $txn) {
                yield [
                    $txn->created_at->format('Y-m-d'),
                    $txn->created_at->format('g:i A'),
                    $txn->isCashIn() ? 'Cash In' : 'Cash Out',
                    $txn->mobile_number,
                    number_format((float) $txn->amount, 2, '.', ''),
                    number_format((float) $txn->service_charge, 2, '.', ''),
                    $txn->charge_paid_in === 'gcash' ? 'GCash' : 'Cash',
                    $txn->reference_number,
                    $txn->user?->name,
                ];
            }
        });
    }

    public function sessions(Request $request): StreamedResponse
    {
        $range = $request->validate([
            'range' => ['nullable', 'in:week,month,last30,all'],
        ])['range'] ?? 'week';

        $query = DailySession::query()->withCount('transactions')->withSum('transactions', 'service_charge');

        match ($range) {
            'week' => $query->where('started_at', '>=', now()->startOfWeek()),
            'month' => $query->where('started_at', '>=', now()->startOfMonth()),
            'last30' => $query->where('started_at', '>=', now()->subDays(30)),
            default => null,
        };

        return $this->stream('gtrack-daily-balances-'.now()->format('Y-m-d').'.csv', [
            'Date', 'Opened', 'Closed', 'Duration', 'Status',
            'Cash Opening', 'Cash Closing', 'GCash Opening', 'GCash Closing',
            'Transactions', 'Earned',
        ], function () use ($query) {
            foreach ($query->orderBy('started_at')->cursor() as $session) {
                yield [
                    $session->started_at->format('Y-m-d'),
                    $session->started_at->format('g:i A'),
                    $session->ended_at?->format('g:i A') ?? '',
                    $session->duration() ?? '',
                    // An auto-closed day was never counted by a human — the
                    // export must not present a guess as a verified figure.
                    $session->auto_closed ? 'Auto-closed' : ($session->isActive() ? 'Active' : 'Closed'),
                    number_format((float) $session->opening_cash_balance, 2, '.', ''),
                    $session->closing_cash_balance === null ? '' : number_format((float) $session->closing_cash_balance, 2, '.', ''),
                    number_format((float) $session->opening_gcash_balance, 2, '.', ''),
                    $session->closing_gcash_balance === null ? '' : number_format((float) $session->closing_gcash_balance, 2, '.', ''),
                    $session->transactions_count,
                    number_format((float) ($session->transactions_sum_service_charge ?? 0), 2, '.', ''),
                ];
            }
        });
    }

    /**
     * @param  array<int, string>  $headers
     * @param  callable(): \Generator<array<int, mixed>>  $rows
     */
    private function stream(string $filename, array $headers, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Excel assumes the system codepage unless a UTF-8 BOM says otherwise,
            // which is what turns the peso sign into mojibake.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($rows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyTransactionFilters(Builder $query, array $filters): void
    {
        match ($filters['range'] ?? 'today') {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'custom' => $this->applyCustomRange($query, $filters),
            default => null,
        };

        if (($filters['type'] ?? 'all') !== 'all') {
            $query->where('type', $filters['type']);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }
    }

    private function applyCustomRange(Builder $query, array $filters): void
    {
        if ($filters['from'] ?? null) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to'] ?? null) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }
}
