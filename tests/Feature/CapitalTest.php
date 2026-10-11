<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\CapitalEntry;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapitalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // The Capital page and its entries are owner-only.
        $this->user = User::factory()->create(['role' => User::ROLE_OWNER]);
    }

    private function openDay(float $gcash = 50000, float $cash = 40000): DailySession
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

    private function entry(string $kind, float $amount, string $source, array $extra = [])
    {
        return $this->actingAs($this->user)->post(route('capital.entries.store'), array_merge([
            'kind' => $kind,
            'amount' => $amount,
            'source' => $source,
        ], $extra));
    }

    /** Start from the owner's ₱75,000 puhunan, recorded as bookkeeping only. */
    private function withPuhunan(float $amount = 75000): void
    {
        $this->entry('capital', $amount, 'none')->assertSessionHas('swal.icon', 'success');
    }

    /**
     * The owner's own sheet: puhunan 75,000, took 30,000 for grocery, then paid
     * back 2,000 → 47,000 should be in the GCash business, 28,000 still owed.
     */
    public function test_the_owners_worked_example_adds_up(): void
    {
        $this->openDay();
        $this->withPuhunan(75000);

        $this->entry('borrow', 30000, 'cash', ['purpose' => 'grocery']);

        $this->assertEquals(75000.00, CapitalEntry::puhunan());
        $this->assertEquals(30000.00, CapitalEntry::utang());
        $this->assertEquals(45000.00, CapitalEntry::puhunan() - CapitalEntry::utang());

        $this->entry('repay', 2000, 'cash');

        $this->assertEquals(28000.00, CapitalEntry::utang());
        $this->assertEquals(47000.00, CapitalEntry::puhunan() - CapitalEntry::utang());
    }

    public function test_taking_cash_from_the_blue_box_lowers_cash_only(): void
    {
        $this->openDay(gcash: 1000, cash: 40000);

        $this->entry('borrow', 30000, 'cash', ['purpose' => 'grocery'])->assertSessionHas('swal.icon', 'success');

        $balance = Balance::current()->fresh();
        $this->assertEquals(10000.00, $balance->cash_balance);
        $this->assertEquals(1000.00, $balance->gcash_balance);
        $this->assertSame('grocery', CapitalEntry::sole()->purpose);
    }

    public function test_paying_a_bill_from_the_wallet_lowers_gcash_only(): void
    {
        $this->openDay(gcash: 5000, cash: 100);

        $this->entry('borrow', 1500, 'gcash', ['purpose' => 'kuryente']);

        $balance = Balance::current()->fresh();
        $this->assertEquals(3500.00, $balance->gcash_balance);
        $this->assertEquals(100.00, $balance->cash_balance);
        $this->assertEquals(1500.00, CapitalEntry::utang());
    }

    public function test_it_cannot_take_more_than_the_balance_holds(): void
    {
        $this->openDay(gcash: 1000, cash: 300);

        $this->entry('borrow', 500, 'cash')->assertSessionHas('swal.icon', 'error');
        $this->entry('borrow', 5000, 'gcash')->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, CapitalEntry::count());
        $this->assertEquals(300.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_a_borrow_must_say_where_it_came_from(): void
    {
        $this->openDay();

        $this->entry('borrow', 500, 'none')->assertSessionHasErrors('source');

        $this->assertSame(0, CapitalEntry::count());
    }

    public function test_paying_back_into_the_drawer_raises_cash(): void
    {
        $this->openDay(gcash: 1000, cash: 0);
        $this->entry('capital', 10000, 'none');
        $this->entry('borrow', 0.01, 'gcash'); // utang to pay down
        $this->entry('borrow', 999.99, 'gcash');

        $this->entry('repay', 500, 'cash')->assertSessionHas('swal.icon', 'success');

        $this->assertEquals(500.00, Balance::current()->fresh()->cash_balance);
        $this->assertEquals(500.00, CapitalEntry::utang());
    }

    public function test_paying_back_into_the_wallet_raises_gcash(): void
    {
        $this->openDay(gcash: 2000, cash: 0);
        $this->entry('borrow', 1000, 'gcash');

        $this->entry('repay', 400, 'gcash');

        $this->assertEquals(1400.00, Balance::current()->fresh()->gcash_balance);
        $this->assertEquals(600.00, CapitalEntry::utang());
    }

    /**
     * Repaying out of earnings: the tubo is already in the wallet, so only
     * the ledger changes — and it can be written at night with no day open.
     */
    public function test_repaying_out_of_earnings_moves_nothing_and_needs_no_open_day(): void
    {
        $session = $this->openDay(gcash: 5000, cash: 5000);
        $this->entry('borrow', 1000, 'cash');
        $session->close(5000, 4000);
        Balance::current()->update(['gcash_balance' => 5000, 'cash_balance' => 0]);

        $this->entry('repay', 300, 'none')->assertSessionHas('swal.icon', 'success');

        $balance = Balance::current()->fresh();
        $this->assertEquals(5000.00, $balance->gcash_balance);
        $this->assertEquals(0.00, $balance->cash_balance);
        $this->assertEquals(700.00, CapitalEntry::utang());
        $this->assertNull(CapitalEntry::latest('id')->first()->balance);
    }

    public function test_moving_cash_or_gcash_needs_an_open_day(): void
    {
        Balance::current()->update(['gcash_balance' => 1000, 'cash_balance' => 1000]);

        $this->entry('borrow', 100, 'cash')->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, CapitalEntry::count());
        $this->assertEquals(1000.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_you_cannot_pay_back_more_than_is_owed(): void
    {
        $this->openDay();
        $this->entry('borrow', 1000, 'cash');

        $this->entry('repay', 1500, 'none')->assertSessionHas('swal.icon', 'error');

        $this->assertEquals(1000.00, CapitalEntry::utang());
    }

    public function test_added_puhunan_can_be_reinvested_earnings_or_new_cash(): void
    {
        $this->openDay(gcash: 1000, cash: 1000);

        $this->entry('capital', 5000, 'none');
        $this->assertEquals(5000.00, CapitalEntry::puhunan());
        $this->assertEquals(1000.00, Balance::current()->fresh()->cash_balance);

        $this->entry('capital', 2000, 'cash');
        $this->assertEquals(7000.00, CapitalEntry::puhunan());
        $this->assertEquals(3000.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_it_never_touches_the_cash_in_out_books(): void
    {
        $session = $this->openDay();
        $this->entry('borrow', 1000, 'cash');
        $this->entry('repay', 500, 'gcash');

        $this->assertSame(0, Transaction::count());
        $this->assertEquals(0.0, $session->fresh()->earnings());
    }

    public function test_the_page_shows_puhunan_utang_and_what_should_be_in_gcash(): void
    {
        $this->openDay();
        $this->withPuhunan(75000);
        $this->entry('borrow', 30000, 'cash', ['purpose' => 'grocery']);

        $this->actingAs($this->user)->get(route('capital'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['puhunan'] === 75000.0
                && $s['utang'] === 30000.0
                && $s['expected'] === 45000.0)
            ->assertSee('₱45,000.00');
    }

    public function test_the_ledger_is_paginated_and_nothing_is_lost_between_pages(): void
    {
        foreach (range(1, 25) as $n) {
            CapitalEntry::create([
                'user_id' => $this->user->id,
                'kind' => CapitalEntry::KIND_CAPITAL,
                'amount' => $n,
            ]);
        }

        $this->actingAs($this->user)->get(route('capital'))
            ->assertOk()
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 25
                && $entries->perPage() === 20
                && count($entries->items()) === 20)
            ->assertSee('page=2');

        $this->actingAs($this->user)->get(route('capital', ['page' => 2]))
            ->assertViewHas('entries', fn ($entries) => count($entries->items()) === 5);
    }

    /**
     * Ending the day and starting the next one must leave the ledger and the
     * figures exactly as they were — it isn't a daily thing.
     */
    public function test_the_ledger_and_figures_survive_ending_and_starting_a_day(): void
    {
        $this->openDay(gcash: 50000, cash: 40000);
        $this->withPuhunan(75000);
        $this->entry('borrow', 30000, 'cash', ['purpose' => 'grocery']);

        $this->actingAs($this->user)->post(route('day.end'))->assertRedirect();
        $this->actingAs($this->user)->post(route('day.start'), ['starting_gcash' => 50000, 'starting_cash' => 10000])->assertRedirect();

        $this->assertSame(2, CapitalEntry::count());
        $this->assertEquals(75000.00, CapitalEntry::puhunan());
        $this->assertEquals(30000.00, CapitalEntry::utang());

        $this->actingAs($this->user)->get(route('capital'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $s) => $s['expected'] === 45000.0)
            ->assertSee('Grocery');
    }

    public function test_guests_cannot_see_or_record_anything(): void
    {
        $this->get(route('capital'))->assertRedirect(route('login'));
        $this->post(route('capital.entries.store'), ['kind' => 'capital', 'amount' => 100, 'source' => 'none'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, CapitalEntry::count());
    }
}
