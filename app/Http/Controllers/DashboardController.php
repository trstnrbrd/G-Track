<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $balance = Balance::current();
        $session = DailySession::active();

        $stats = [
            'cash_in' => 0,
            'cash_out' => 0,
            'service_charge' => 0,
            'count' => 0,
        ];
        $recent = collect();

        if ($session) {
            $base = Transaction::where('daily_session_id', $session->id);

            $stats['cash_in'] = (clone $base)->where('type', 'cash_in')->sum('amount');
            $stats['cash_out'] = (clone $base)->where('type', 'cash_out')->sum('amount');
            $stats['service_charge'] = (clone $base)->sum('service_charge');
            $stats['count'] = (clone $base)->count();

            $recent = (clone $base)->latest()->take(8)->get();
        }

        // The modal renders its rate guide and live preview from the same
        // brackets the server charges from — never a second hardcoded copy.
        $chargeBrackets = config('gtrack.service_charge_brackets');

        // Suki customers use the same number every week. Offering the ones we
        // have seen before turns the most tedious field into a tap.
        $knownCustomers = Transaction::query()
            ->whereNotNull('mobile_number')
            ->select('mobile_number')
            ->distinct()
            ->latest('id')
            ->limit(50)
            ->pluck('mobile_number');

        $quickAmounts = config('gtrack.quick_amounts');

        return view('dashboard', compact(
            'balance', 'session', 'stats', 'recent',
            'chargeBrackets', 'knownCustomers', 'quickAmounts',
        ));
    }
}
