<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordStoreSaleRequest;
use App\Models\Balance;
use App\Models\DailySession;
use App\Models\StoreSale;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreSaleController extends Controller
{
    /**
     * Store items paid for with GCash, and how much cash has been moved to the
     * tindahan because of them.
     */
    public function index(): View
    {
        $session = DailySession::active();

        $today = $session
            ? StoreSale::where('daily_session_id', $session->id)
            : null;

        $summary = [
            'moved_today' => $today ? (float) (clone $today)->sum('amount') : 0.0,
            'count_today' => $today ? (clone $today)->count() : 0,
            'moved_all_time' => (float) StoreSale::sum('amount'),
        ];

        $sales = StoreSale::query()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return view('store', [
            'session' => $session,
            'balance' => Balance::current(),
            'summary' => $summary,
            'sales' => $sales,
        ]);
    }

    /**
     * Record a GCash-paid item and move the matching cash to the tindahan.
     *
     * Same locking as recording a transaction: one database transaction, with
     * the open session and the balances row locked, so it can't interleave
     * with a cash in / cash out happening at the same moment.
     */
    public function store(RecordStoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $amount = round((float) $data['amount'], 2);
        $deltas = StoreSale::deltasFor($amount);

        try {
            DB::transaction(function () use ($request, $data, $amount, $deltas) {
                $session = DailySession::activeLocked();

                if (! $session) {
                    throw new \DomainException('You need to start the day before you can record a store sale.');
                }

                $balance = Balance::locked();

                // The GCash side only goes up; the drawer is what can run short.
                if (! $balance->canAbsorb($deltas)) {
                    throw new \DomainException(sprintf(
                        'Not enough cash on hand to move to the store. You need ₱%s but the drawer only has ₱%s.',
                        number_format($amount, 2),
                        number_format((float) $balance->cash_balance, 2),
                    ));
                }

                StoreSale::create([
                    'daily_session_id' => $session->id,
                    'user_id' => $request->user()->id,
                    'amount' => $amount,
                    'reference_number' => $data['reference_number'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);

                $balance->applyDeltas($deltas);
            });
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Not recorded',
                    'text' => $e->getMessage(),
                ]);
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->with('swal', [
                    'icon' => 'warning',
                    'title' => 'Already recorded',
                    'text' => 'That reference number is already in the books. Nothing was recorded twice.',
                ]);
        }

        $peso = '₱'.number_format($amount, 2);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Moved to the store',
            'text' => "GCash +{$peso}, cash on hand −{$peso}. Take {$peso} from the GCash drawer and put it in the store.",
        ]);
    }
}
