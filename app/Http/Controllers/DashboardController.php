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

        return view('dashboard', compact('balance', 'session', 'stats', 'recent'));
    }
}
