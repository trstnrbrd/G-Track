<?php

namespace Tests\Unit;

use App\Models\Transaction;
use Tests\TestCase;

/**
 * The core money rules, tested without touching the database.
 *
 * The four scenarios below are the real ones from behind the counter. If any of
 * these break, the shop's books are wrong.
 */
class TransactionRulesTest extends TestCase
{
    /** Scenario 1: "Pa cash in 500" — customer hands over 515 cash. */
    public function test_cash_in_with_the_charge_paid_separately_in_cash(): void
    {
        $deltas = Transaction::deltasFor('cash_in', 500, 15, 'cash');

        $this->assertSame(515.0, $deltas['cash'], 'Shop receives the amount plus the fee in cash');
        $this->assertSame(-500.0, $deltas['gcash'], 'Shop sends exactly the requested amount');
    }

    /** Scenario 2: "Ibawas na lang sa cash in" — customer hands 500, receives 485. */
    public function test_cash_in_with_the_charge_deducted_from_the_amount(): void
    {
        $deltas = Transaction::deltasFor('cash_in', 500, 15, 'gcash');

        $this->assertSame(500.0, $deltas['cash'], 'Shop receives only the amount in cash');
        $this->assertSame(-485.0, $deltas['gcash'], 'Shop sends the amount less the fee');
    }

    /** Scenario 3: "Pa cash out 500" — customer sends 500, pays the 15 in cash. */
    public function test_cash_out_with_the_charge_paid_separately_in_cash(): void
    {
        $deltas = Transaction::deltasFor('cash_out', 500, 15, 'cash');

        $this->assertSame(500.0, $deltas['gcash'], 'Shop receives the amount in GCash');
        $this->assertSame(-485.0, $deltas['cash'], 'Shop hands out 500 but takes 15 back as the fee');
    }

    /** Scenario 4: customer sends 515 in GCash, walks away with 500 cash. */
    public function test_cash_out_with_the_charge_included_in_the_gcash_sent(): void
    {
        $deltas = Transaction::deltasFor('cash_out', 500, 15, 'gcash');

        $this->assertSame(515.0, $deltas['gcash'], 'Shop receives the amount plus the fee in GCash');
        $this->assertSame(-500.0, $deltas['cash'], 'Shop hands out exactly the requested amount');
    }

    /**
     * Whichever way the fee is settled, the shop's net position improves by
     * exactly the service charge. This is the invariant that matters.
     */
    public function test_every_arrangement_nets_the_shop_exactly_the_service_charge(): void
    {
        foreach (['cash_in', 'cash_out'] as $type) {
            foreach (['cash', 'gcash'] as $paidIn) {
                $deltas = Transaction::deltasFor($type, 500, 15, $paidIn);

                $this->assertSame(
                    15.0,
                    round($deltas['cash'] + $deltas['gcash'], 2),
                    "Net position wrong for {$type} with the fee paid in {$paidIn}",
                );
            }
        }
    }

    public function test_service_charge_brackets_match_the_posted_rates(): void
    {
        $expected = [
            1 => 10, 500 => 10,
            501 => 15, 1000 => 15,
            1001 => 20, 2500 => 20,
            2501 => 25, 5000 => 25,
            5001 => 30, 10000 => 30,
            10001 => 50, 20000 => 50,
            20001 => 100, 50000 => 100,
        ];

        foreach ($expected as $amount => $fee) {
            $this->assertSame(
                (float) $fee,
                Transaction::serviceChargeFor($amount),
                "Wrong charge at ₱{$amount}",
            );
        }
    }

    public function test_bracket_boundaries_do_not_leave_gaps(): void
    {
        // A boundary amount must fall in the lower bracket, not the higher one.
        $this->assertSame(10.0, Transaction::serviceChargeFor(500.00));
        $this->assertSame(15.0, Transaction::serviceChargeFor(500.01));
    }

    public function test_centavo_amounts_do_not_drift(): void
    {
        $deltas = Transaction::deltasFor('cash_in', 1234.56, 20, 'cash');

        $this->assertSame(1254.56, $deltas['cash']);
        $this->assertSame(-1234.56, $deltas['gcash']);
    }
}
