<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordCapitalEntryRequest;
use App\Models\Balance;
use App\Models\CapitalEntry;
use App\Models\DailySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CapitalController extends Controller
{
    public function index(): View
    {
        $balance = Balance::current();

        $puhunan = CapitalEntry::puhunan();
        $utang = CapitalEntry::utang();

        return view('capital', [
            'session' => DailySession::active(),
            'balance' => $balance,
            'summary' => [
                'puhunan' => $puhunan,
                'utang' => $utang,
                // What should be in the GCash business if nothing else moved.
                'expected' => round($puhunan - $utang, 2),
                'actual' => round((float) $balance->gcash_balance + (float) $balance->cash_balance, 2),
            ],
            'entries' => CapitalEntry::query()
                ->with('user')
                ->latest('created_at')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    /**
     * Record a kumuha / nagbayad / dagdag puhunan.
     *
     * Same locking as recording a transaction. An entry that moves cash or
     * GCash needs an open day (it changes the till); one that moves nothing is
     * only bookkeeping and can be written any time, e.g. at night.
     */
    public function store(RecordCapitalEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kind = $data['kind'];
        $amount = round((float) $data['amount'], 2);
        $balanceName = $data['source'] === 'none' ? null : $data['source'];
        $deltas = CapitalEntry::deltasFor($kind, $amount, $balanceName);

        try {
            DB::transaction(function () use ($request, $data, $kind, $amount, $balanceName, $deltas) {
                $session = DailySession::activeLocked();
                $balance = Balance::locked();

                if ($balanceName !== null && ! $session) {
                    throw new \DomainException('You need to start the day before you can move cash or GCash.');
                }

                if ($kind === CapitalEntry::KIND_REPAY && $amount > CapitalEntry::utang() + 0.001) {
                    throw new \DomainException(sprintf(
                        'The debt is only ₱%s, so you can\'t pay back ₱%s.',
                        number_format(CapitalEntry::utang(), 2),
                        number_format($amount, 2),
                    ));
                }

                if (! $balance->canAbsorb($deltas)) {
                    throw new \DomainException($balanceName === CapitalEntry::BALANCE_GCASH
                        ? sprintf('Not enough GCash. You need ₱%s but the wallet only has ₱%s.', number_format($amount, 2), number_format((float) $balance->gcash_balance, 2))
                        : sprintf('Not enough cash on hand. You need ₱%s but the drawer only has ₱%s.', number_format($amount, 2), number_format((float) $balance->cash_balance, 2)));
                }

                CapitalEntry::create([
                    'daily_session_id' => $session?->id,
                    'user_id' => $request->user()->id,
                    'kind' => $kind,
                    'amount' => $amount,
                    'balance' => $balanceName,
                    'purpose' => $kind === CapitalEntry::KIND_BORROW ? ($data['purpose'] ?? null) : null,
                    'note' => $data['note'] ?? null,
                ]);

                if ($balanceName !== null) {
                    $balance->applyDeltas($deltas);
                }
            });
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Not recorded',
                    'text' => $e->getMessage(),
                ]);
        }

        return back()->with('swal', [
            'icon' => 'success',
            'title' => match ($kind) {
                CapitalEntry::KIND_BORROW => 'Borrow recorded',
                CapitalEntry::KIND_REPAY => 'Repayment recorded',
                default => 'Capital added',
            },
            'text' => sprintf(
                'Capital ₱%s · Debt ₱%s · Should be in GCash ₱%s',
                number_format(CapitalEntry::puhunan(), 2),
                number_format(CapitalEntry::utang(), 2),
                number_format(CapitalEntry::puhunan() - CapitalEntry::utang(), 2),
            ),
        ]);
    }
}
