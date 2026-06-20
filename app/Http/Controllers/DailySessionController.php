<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\DailySession;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class DailySessionController extends Controller
{
    /**
     * Start the day — opens a new session and records the opening balances.
     */
    public function start(Request $request): RedirectResponse
    {
        if (DailySession::active()) {
            return back()->with('swal', [
                'icon' => 'info',
                'title' => 'Day already started',
                'text' => 'There is already an active session.',
            ]);
        }

        // Starting balances entered by the owner in the Start Day modal.
        $validated = $request->validate([
            'starting_gcash' => ['required', 'numeric', 'gt:0'],
            'starting_cash' => ['required', 'numeric', 'gt:0'],
        ]);

        // Set the live balances to the entered starting values (reflects on the dashboard).
        $balance = Balance::current();
        $balance->update([
            'gcash_balance' => $validated['starting_gcash'],
            'cash_balance' => $validated['starting_cash'],
        ]);

        // Record them as this session's opening balances.
        DailySession::create([
            'user_id' => $request->user()->id,
            'opening_gcash_balance' => $validated['starting_gcash'],
            'opening_cash_balance' => $validated['starting_cash'],
            'status' => 'active',
            'started_at' => now(),
        ]);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Day Started!',
            'text' => 'You can now log cash-in and cash-out transactions.',
        ]);
    }

    /**
     * End the day — closes the active session and records the closing balances.
     */
    public function end(Request $request): RedirectResponse
    {
        $session = DailySession::active();

        if (! $session) {
            return back()->with('swal', [
                'icon' => 'info',
                'title' => 'No active session',
                'text' => 'There is no day to end right now.',
            ]);
        }

        $balance = Balance::current();

        // Record the closing balances on the session (kept for history / reconciliation).
        $session->update([
            'closing_gcash_balance' => $balance->gcash_balance,
            'closing_cash_balance' => $balance->cash_balance,
            'status' => 'closed',
            'ended_at' => now(),
        ]);

        // Reset the live balances to zero — the day is over.
        $balance->update([
            'gcash_balance' => 0,
            'cash_balance' => 0,
        ]);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Day Ended',
            'text' => 'The session has been closed and balances were reset.',
        ]);
    }
}
