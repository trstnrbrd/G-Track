<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function startDay(float $gcash = 5000, float $cash = 3000)
    {
        return $this->actingAs($this->user)->post(route('day.start'), [
            'starting_gcash' => $gcash,
            'starting_cash' => $cash,
        ]);
    }

    public function test_starting_the_day_opens_a_session_and_sets_the_balances(): void
    {
        $this->startDay(5000, 3000)->assertRedirect();

        $session = DailySession::sole();
        $this->assertTrue($session->isActive());
        $this->assertEquals(5000.00, $session->opening_gcash_balance);
        $this->assertEquals(3000.00, $session->opening_cash_balance);

        $balance = Balance::current()->fresh();
        $this->assertEquals(5000.00, $balance->gcash_balance);
        $this->assertEquals(3000.00, $balance->cash_balance);
    }

    /** The shop can legitimately open with an empty drawer. */
    public function test_the_day_can_start_with_zero_balances(): void
    {
        $this->startDay(0, 0)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue(DailySession::sole()->isActive());
    }

    public function test_negative_starting_balances_are_rejected(): void
    {
        $this->startDay(-1, 100)->assertSessionHasErrors('starting_gcash');

        $this->assertSame(0, DailySession::count());
    }

    /**
     * A double-click, a refresh, or two staff starting at once must never split
     * the day across two sessions — the dashboard reads one and transactions
     * would land on the other.
     */
    public function test_starting_the_day_twice_does_not_open_a_second_session(): void
    {
        $this->startDay();
        $this->startDay()->assertRedirect()->assertSessionHas('swal.icon', 'info');

        $this->assertSame(1, DailySession::count());
        $this->assertSame(1, DailySession::where('status', 'active')->count());
    }

    /**
     * Physical cash leaves the drawer at closing, but the GCash float stays in
     * the wallet overnight. Configured in config/gtrack.php.
     */
    public function test_ending_the_day_records_closing_balances_and_carries_the_gcash_float_over(): void
    {
        $this->startDay(5000, 3000);

        $this->actingAs($this->user)->post(route('day.end'))->assertRedirect();

        $session = DailySession::sole();
        $this->assertFalse($session->isActive());
        $this->assertEquals(5000.00, $session->closing_gcash_balance);
        $this->assertEquals(3000.00, $session->closing_cash_balance);
        $this->assertNotNull($session->ended_at);

        $balance = Balance::current()->fresh();
        $this->assertEquals(5000.00, $balance->gcash_balance, 'GCash float carries over to tomorrow');
        $this->assertEquals(0.00, $balance->cash_balance, 'Cash comes out of the drawer at closing');
    }

    /**
     * Closing must release the unique active_guard, or every future Start Day
     * would collide with the closed session's held value.
     */
    public function test_a_new_day_can_be_started_after_the_previous_one_is_closed(): void
    {
        $this->startDay(5000, 3000);
        $this->actingAs($this->user)->post(route('day.end'));

        $this->startDay(5000, 1000)->assertRedirect()->assertSessionHas('swal.icon', 'success');

        $this->assertSame(2, DailySession::count());
        $this->assertSame(1, DailySession::where('status', 'active')->count());
    }

    public function test_ending_a_day_that_was_never_started_changes_nothing(): void
    {
        $this->actingAs($this->user)->post(route('day.end'))
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'info');

        $this->assertSame(0, DailySession::count());
    }

    public function test_the_closing_summary_reports_the_days_earnings(): void
    {
        $this->startDay(10000, 10000);
        $session = DailySession::sole();

        foreach ([500, 1500] as $amount) {
            Transaction::create([
                'daily_session_id' => $session->id,
                'user_id' => $this->user->id,
                'type' => 'cash_in',
                'amount' => $amount,
                'service_charge' => Transaction::serviceChargeFor($amount),
                'charge_paid_in' => 'cash',
            ]);
        }

        // ₱10 on the 500, ₱20 on the 1,500.
        $this->assertSame(30.0, $session->fresh()->earnings());
    }

    public function test_guests_cannot_start_or_end_the_day(): void
    {
        $this->post(route('day.start'), ['starting_gcash' => 100, 'starting_cash' => 100])
            ->assertRedirect(route('login'));

        $this->assertSame(0, DailySession::count());
    }
}
