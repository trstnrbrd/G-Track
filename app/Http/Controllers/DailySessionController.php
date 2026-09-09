<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\DailySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailySessionController extends Controller
{
    /**
     * Start the day — opens a session and records the opening balances.
     */
    public function start(Request $request): RedirectResponse
    {
        // Zero is legitimate: the shop can open with an empty drawer, or with
        // no GCash float until the owner tops up.
        $validated = $request->validate([
            'starting_gcash' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'starting_cash' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ]);

        $alreadyOpen = false;

        DB::transaction(function () use ($request, $validated, &$alreadyOpen) {
            // Locked, so two rapid clicks can't both pass the check and open
            // two sessions — which would silently split the day's transactions.
            if (DailySession::activeLocked()) {
                $alreadyOpen = true;

                return;
            }

            $balance = Balance::locked();

            $balance->update([
                'gcash_balance' => $validated['starting_gcash'],
                'cash_balance' => $validated['starting_cash'],
            ]);

            DailySession::create([
                'user_id' => $request->user()->id,
                'opening_gcash_balance' => $validated['starting_gcash'],
                'opening_cash_balance' => $validated['starting_cash'],
                'status' => DailySession::STATUS_ACTIVE,
                'active_guard' => 1,
                'started_at' => now(),
            ]);
        });

        if ($alreadyOpen) {
            return back()->with('swal', [
                'icon' => 'info',
                'title' => 'Day already started',
                'text' => 'There is already an active session.',
            ]);
        }

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Day Started!',
            'text' => 'You can now log cash-in and cash-out transactions.',
        ]);
    }

    /**
     * End the day — closes the session and records the closing balances.
     *
     * Physical cash comes out of the drawer at closing, so the cash balance
     * resets to zero. The GCash float stays in the wallet overnight and carries
     * over as tomorrow's opening balance. Configured in config/gtrack.php.
     */
    public function end(Request $request): RedirectResponse
    {
        $session = null;

        DB::transaction(function () use (&$session) {
            $session = DailySession::activeLocked();

            if (! $session) {
                return;
            }

            $balance = Balance::locked();

            $session->close(
                (float) $balance->gcash_balance,
                (float) $balance->cash_balance,
            );

            $resets = config('gtrack.end_day_resets');

            $balance->update([
                'gcash_balance' => $resets['gcash'] ? 0 : $balance->gcash_balance,
                'cash_balance' => $resets['cash'] ? 0 : $balance->cash_balance,
            ]);
        });

        if (! $session) {
            return back()->with('swal', [
                'icon' => 'info',
                'title' => 'No active session',
                'text' => 'There is no day to end right now.',
            ]);
        }

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Day Ended',
            'text' => sprintf(
                'Closed with ₱%s cash and ₱%s GCash. Earnings today: ₱%s. The GCash float carries over to tomorrow.',
                number_format((float) $session->closing_cash_balance, 2),
                number_format((float) $session->closing_gcash_balance, 2),
                number_format($session->earnings(), 2),
            ),
        ]);
    }
}
