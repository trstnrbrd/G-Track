<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function openDay(float $gcash = 10000, float $cash = 10000): DailySession
    {
        Balance::current()->update(['gcash_balance' => $gcash, 'cash_balance' => $cash]);

        return DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => $gcash,
            'opening_cash_balance' => $cash,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);
    }

    private function record(array $overrides = [])
    {
        return $this->actingAs($this->user)->post(route('transactions.store'), array_merge([
            'type' => 'cash_in',
            'amount' => 500,
            'charge_paid_in' => 'cash',
            'mobile_number' => '09171234567',
        ], $overrides));
    }

    public function test_a_cash_in_moves_both_balances_and_is_recorded(): void
    {
        $session = $this->openDay(gcash: 10000, cash: 2000);

        $this->record(['amount' => 500, 'charge_paid_in' => 'cash'])->assertRedirect();

        $balance = Balance::current()->fresh();

        // ₱500 sits in the ₱1–₱500 bracket, so the charge is ₱10.
        // cash 2000 + 510, gcash 10000 - 500
        $this->assertEquals(2510.00, $balance->cash_balance);
        $this->assertEquals(9500.00, $balance->gcash_balance);

        $transaction = Transaction::sole();
        $this->assertSame($session->id, $transaction->daily_session_id);
        $this->assertSame($this->user->id, $transaction->user_id);
        $this->assertEquals(10.00, $transaction->service_charge);
    }

    public function test_a_cash_out_moves_both_balances_the_other_way(): void
    {
        $this->openDay(gcash: 1000, cash: 5000);

        $this->record([
            'type' => 'cash_out',
            'amount' => 500,
            'charge_paid_in' => 'gcash',
            'reference_number' => '1234567890123',
        ])->assertRedirect();

        $balance = Balance::current()->fresh();

        // gcash 1000 + 510, cash 5000 - 500
        $this->assertEquals(1510.00, $balance->gcash_balance);
        $this->assertEquals(4500.00, $balance->cash_balance);
    }

    /**
     * The single most important guard: a tampered or stale client must not be
     * able to set its own fee.
     */
    public function test_a_service_charge_sent_by_the_client_is_ignored(): void
    {
        $this->openDay();

        $this->record(['amount' => 500, 'service_charge' => 0])->assertRedirect();

        $this->assertEquals(10.00, Transaction::sole()->service_charge);
    }

    public function test_a_transaction_is_refused_when_no_day_is_open(): void
    {
        Balance::current()->update(['gcash_balance' => 10000, 'cash_balance' => 10000]);

        $this->record()->assertRedirect()->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, Transaction::count());
        $this->assertEquals(10000.00, Balance::current()->fresh()->gcash_balance);
    }

    public function test_a_cash_in_is_refused_when_the_gcash_wallet_is_short(): void
    {
        $this->openDay(gcash: 400, cash: 10000);

        $this->record(['amount' => 500])->assertRedirect()->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, Transaction::count());
        $this->assertEquals(400.00, Balance::current()->fresh()->gcash_balance);
    }

    public function test_a_cash_out_is_refused_when_the_drawer_is_short(): void
    {
        $this->openDay(gcash: 10000, cash: 100);

        $this->record([
            'type' => 'cash_out',
            'amount' => 500,
            'reference_number' => 'REF123',
        ])->assertRedirect()->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, Transaction::count());
        $this->assertEquals(100.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_a_cash_out_requires_the_customers_reference_number(): void
    {
        $this->openDay();

        $this->record(['type' => 'cash_out', 'reference_number' => null])
            ->assertSessionHasErrors('reference_number');

        $this->assertSame(0, Transaction::count());
    }

    public function test_a_cash_in_requires_the_customers_mobile_number(): void
    {
        $this->openDay();

        $this->record(['mobile_number' => null])->assertSessionHasErrors('mobile_number');

        $this->assertSame(0, Transaction::count());
    }

    public function test_mobile_numbers_are_normalised_before_being_stored(): void
    {
        $this->openDay();

        $this->record(['mobile_number' => '+63 917 123 4567'])->assertRedirect();

        $this->assertSame('09171234567', Transaction::sole()->mobile_number);
    }

    public function test_amounts_typed_with_separators_are_accepted(): void
    {
        $this->openDay(gcash: 10000, cash: 10000);

        $this->record(['amount' => '1,500.00'])->assertRedirect();

        $this->assertEquals(1500.00, Transaction::sole()->amount);
        $this->assertEquals(20.00, Transaction::sole()->service_charge);
    }

    public function test_a_zero_or_negative_amount_is_rejected(): void
    {
        $this->openDay();

        $this->record(['amount' => 0])->assertSessionHasErrors('amount');
        $this->record(['amount' => -100])->assertSessionHasErrors('amount');

        $this->assertSame(0, Transaction::count());
    }

    public function test_guests_cannot_record_transactions(): void
    {
        $this->openDay();

        $this->postJson(route('transactions.store'), [])->assertUnauthorized();

        $this->assertSame(0, Transaction::count());
    }
}
