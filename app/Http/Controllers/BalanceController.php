<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BalanceController extends Controller
{
    /**
     * Day-by-day opening/closing balances for reconciliation.
     *
     * Transaction counts and earnings are aggregated in one grouped query
     * rather than per row, so the page doesn't fire N queries for N days.
     */
    public function index(Request $request): View
    {
        $range = $request->validate([
            'range' => ['nullable', 'in:week,month,last30,all'],
        ])['range'] ?? 'week';

        $query = DailySession::query();
        $this->applyRange($query, $range);

        $sessions = $query->latest('started_at')->paginate(20)->withQueryString();

        $totals = Transaction::query()
            ->selectRaw('daily_session_id, count(*) as txn_count, sum(service_charge) as earnings')
            ->whereIn('daily_session_id', $sessions->pluck('id'))
            ->groupBy('daily_session_id')
            ->get()
            ->keyBy('daily_session_id');

        return view('balance', [
            'sessions' => $sessions,
            'totals' => $totals,
            'range' => $range,
            'balance' => Balance::current(),
        ]);
    }

    private function applyRange(Builder $query, string $range): void
    {
        match ($range) {
            'week' => $query->where('started_at', '>=', now()->startOfWeek()),
            'month' => $query->where('started_at', '>=', now()->startOfMonth()),
            'last30' => $query->where('started_at', '>=', now()->subDays(30)),
            default => null,
        };
    }
}
