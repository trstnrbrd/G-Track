<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Record a cash in / cash out and move the balances to match.
     *
     * Everything that touches money happens inside one database transaction
     * with the balances row locked, so a double-submit or two staff working at
     * once can't interleave and corrupt the figures.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $amount = round((float) $data['amount'], 2);

        // Recomputed server-side; whatever the browser previewed is irrelevant.
        $serviceCharge = Transaction::serviceChargeFor($amount);

        $deltas = Transaction::deltasFor(
            $data['type'],
            $amount,
            $serviceCharge,
            $data['charge_paid_in'],
        );

        try {
            $transaction = DB::transaction(function () use ($request, $data, $amount, $serviceCharge, $deltas) {
                $session = DailySession::activeLocked();

                if (! $session) {
                    throw new \DomainException('You need to start the day before you can record a transaction.');
                }

                $balance = Balance::locked();

                if (! $balance->canAbsorb($deltas)) {
                    throw new \DomainException($this->insufficientFundsMessage($balance, $deltas));
                }

                $transaction = Transaction::create([
                    'daily_session_id' => $session->id,
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'mobile_number' => $data['mobile_number'] ?? null,
                    'amount' => $amount,
                    'service_charge' => $serviceCharge,
                    'charge_paid_in' => $data['charge_paid_in'],
                ]);

                $balance->applyDeltas($deltas);

                return $transaction;
            });
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->with('swal', [
                    'icon' => 'error',
                    'title' => 'Transaction not recorded',
                    'text' => $e->getMessage(),
                ]);
        } catch (UniqueConstraintViolationException) {
            // The form already rejects a duplicate reference, but two submits
            // landing at once can both pass that check. The unique index is the
            // real guard — this just turns its error into plain language.
            return back()
                ->withInput()
                ->with('swal', [
                    'icon' => 'warning',
                    'title' => 'Already recorded',
                    'text' => 'That reference number is already in the books. Nothing was recorded twice.',
                ]);
        }

        return back()->with('swal', [
            'icon' => 'success',
            'title' => $transaction->isCashIn() ? 'Cash In recorded' : 'Cash Out recorded',
            'text' => $this->receiptLine($transaction, $deltas),
        ]);
    }

    /**
     * Spell out which balance fell short and by how much — "insufficient
     * balance" alone leaves the operator guessing at the counter.
     */
    private function insufficientFundsMessage(Balance $balance, array $deltas): string
    {
        if (round((float) $balance->gcash_balance + $deltas['gcash'], 2) < 0) {
            return sprintf(
                'Not enough GCash. You need ₱%s but the wallet only has ₱%s.',
                number_format(abs($deltas['gcash']), 2),
                number_format((float) $balance->gcash_balance, 2),
            );
        }

        return sprintf(
            'Not enough cash on hand. You need ₱%s but the drawer only has ₱%s.',
            number_format(abs($deltas['cash']), 2),
            number_format((float) $balance->cash_balance, 2),
        );
    }

    /**
     * A plain-language confirmation of what actually moved, so the operator can
     * catch a wrong entry immediately rather than at closing time.
     */
    private function receiptLine(Transaction $transaction, array $deltas): string
    {
        $describe = fn (float $delta, string $label): string => sprintf(
            '%s %s ₱%s',
            $label,
            $delta >= 0 ? '+' : '−',
            number_format(abs($delta), 2),
        );

        return sprintf(
            '%s · charge ₱%s · %s, %s',
            '₱'.number_format((float) $transaction->amount, 2),
            number_format((float) $transaction->service_charge, 2),
            $describe($deltas['cash'], 'Cash'),
            $describe($deltas['gcash'], 'GCash'),
        );
    }
}
