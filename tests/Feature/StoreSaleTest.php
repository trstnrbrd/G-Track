<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\StoreSale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function openDay(float $gcash = 1000, float $cash = 1000): DailySession
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

    private function sell(array $overrides = [])
    {
        return $this->actingAs($this->user)->post(route('store.sales.store'), array_merge([
            'amount' => 500,
        ], $overrides));
    }

    /**
     * The owner's own walkthrough: wallet 1,000 and drawer 1,000; a ₱500 item
     * paid via GCash ends with wallet 1,500 and drawer 500 (the 500 went to the
     * tindahan).
     */
    public function test_a_gcash_paid_item_moves_cash_from_the_drawer_to_the_tindahan(): void
    {
        $session = $this->openDay(gcash: 1000, cash: 1000);

        $this->sell(['amount' => 500, 'note' => '1 sack rice'])
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'success');

        $balance = Balance::current()->fresh();
        $this->assertEquals(1500.00, $balance->gcash_balance);
        $this->assertEquals(500.00, $balance->cash_balance);

        $sale = StoreSale::sole();
        $this->assertSame($session->id, $sale->daily_session_id);
        $this->assertSame($this->user->id, $sale->user_id);
        $this->assertEquals(500.00, $sale->amount);
        $this->assertSame('1 sack rice', $sale->note);
    }

    public function test_it_is_refused_when_the_drawer_cannot_cover_it(): void
    {
        $this->openDay(gcash: 1000, cash: 300);

        $this->sell(['amount' => 500])
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'error');

        $this->assertStringContainsString('₱300.00', session('swal.text'));
        $this->assertSame(0, StoreSale::count());

        $balance = Balance::current()->fresh();
        $this->assertEquals(1000.00, $balance->gcash_balance);
        $this->assertEquals(300.00, $balance->cash_balance);
    }

    public function test_the_whole_drawer_can_be_moved(): void
    {
        $this->openDay(gcash: 0, cash: 500);

        $this->sell(['amount' => 500])->assertSessionHas('swal.icon', 'success');

        $this->assertEquals(0.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_it_is_refused_when_no_day_is_open(): void
    {
        Balance::current()->update(['gcash_balance' => 1000, 'cash_balance' => 1000]);

        $this->sell()->assertRedirect()->assertSessionHas('swal.icon', 'error');

        $this->assertSame(0, StoreSale::count());
        $this->assertEquals(1000.00, Balance::current()->fresh()->cash_balance);
    }

    public function test_a_zero_or_negative_amount_is_rejected(): void
    {
        $this->openDay();

        $this->sell(['amount' => 0])->assertSessionHasErrors('amount');
        $this->sell(['amount' => -50])->assertSessionHasErrors('amount');

        $this->assertSame(0, StoreSale::count());
    }

    public function test_amounts_typed_with_separators_are_accepted(): void
    {
        $this->openDay(gcash: 0, cash: 5000);

        $this->sell(['amount' => '₱1,250.50'])->assertSessionHasNoErrors();

        $this->assertEquals(1250.50, StoreSale::sole()->amount);
    }

    public function test_a_reference_already_logged_as_a_transaction_is_rejected(): void
    {
        $session = $this->openDay();
        Transaction::create([
            'daily_session_id' => $session->id,
            'user_id' => $this->user->id,
            'type' => 'cash_out',
            'reference_number' => '1234567890123',
            'amount' => 500,
            'service_charge' => 10,
            'charge_paid_in' => 'cash',
        ]);

        $this->sell(['reference_number' => '1234567890123'])->assertSessionHasErrors('reference_number');

        $this->assertSame(0, StoreSale::count());
    }

    public function test_a_transaction_cannot_reuse_a_store_sale_reference(): void
    {
        $this->openDay(gcash: 10000, cash: 10000);
        $this->sell(['reference_number' => '5555555555555'])->assertSessionHasNoErrors();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'type' => 'cash_out',
            'amount' => 500,
            'charge_paid_in' => 'cash',
            'reference_number' => '5555555555555',
        ])->assertSessionHasErrors('reference_number');

        $this->assertSame(0, Transaction::count());
    }

    /**
     * Store sales aren't GCash services: they earn nothing, and the day's net
     * change across both balances must still equal the service charges.
     */
    public function test_it_earns_nothing_and_leaves_the_net_change_untouched(): void
    {
        $session = $this->openDay(gcash: 1000, cash: 1000);

        $this->sell(['amount' => 500]);

        $balance = Balance::current()->fresh();
        $this->assertEquals(2000.00, (float) $balance->gcash_balance + (float) $balance->cash_balance);
        $this->assertEquals(0.0, $session->fresh()->earnings());
        $this->assertSame(0, Transaction::count());
    }

    public function test_end_day_closes_with_the_cash_that_is_left_after_the_move(): void
    {
        $session = $this->openDay(gcash: 1000, cash: 1000);
        $this->sell(['amount' => 500]);

        $this->actingAs($this->user)->post(route('day.end'))->assertRedirect();

        $session->refresh();
        $this->assertEquals(500.00, $session->closing_cash_balance);
        $this->assertEquals(1500.00, $session->closing_gcash_balance);
    }

    public function test_the_page_shows_what_was_moved_today_and_all_time(): void
    {
        $this->openDay(gcash: 0, cash: 5000);
        $this->sell(['amount' => 500]);
        $this->sell(['amount' => 250]);

        $this->actingAs($this->user)->get(route('store'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['moved_today'] === 750.0
                && $summary['count_today'] === 2
                && $summary['moved_all_time'] === 750.0)
            ->assertSee('₱750.00');
    }

    public function test_the_page_says_why_recording_is_unavailable_with_no_day_open(): void
    {
        $this->actingAs($this->user)->get(route('store'))
            ->assertOk()
            ->assertSee('Start the day on the Dashboard first.');
    }

    public function test_the_list_is_paginated_and_nothing_is_lost_between_pages(): void
    {
        $session = $this->openDay();

        foreach (range(1, 25) as $n) {
            StoreSale::create([
                'daily_session_id' => $session->id,
                'user_id' => $this->user->id,
                'amount' => $n,
            ]);
        }

        $this->actingAs($this->user)->get(route('store'))
            ->assertOk()
            ->assertViewHas('sales', fn ($sales) => $sales->total() === 25
                && $sales->perPage() === 20
                && count($sales->items()) === 20)
            ->assertSee('page=2');

        $this->actingAs($this->user)->get(route('store', ['page' => 2]))
            ->assertViewHas('sales', fn ($sales) => count($sales->items()) === 5);
    }

    public function test_sales_and_todays_total_survive_ending_the_day(): void
    {
        $this->openDay(gcash: 1000, cash: 1000);
        $this->sell(['amount' => 500, 'note' => '1 sack rice']);

        $this->actingAs($this->user)->post(route('day.end'))->assertRedirect();

        // No day is open now, but the sale and today's figure are still there.
        $this->actingAs($this->user)->get(route('store'))
            ->assertOk()
            ->assertSee('1 sack rice')
            ->assertViewHas('summary', fn (array $s) => $s['moved_today'] === 500.0
                && $s['count_today'] === 1
                && $s['moved_all_time'] === 500.0);

        $this->assertSame(1, StoreSale::count());
    }

    public function test_guests_cannot_see_or_record_store_sales(): void
    {
        $this->get(route('store'))->assertRedirect(route('login'));
        $this->post(route('store.sales.store'), ['amount' => 500])->assertRedirect(route('login'));

        $this->assertSame(0, StoreSale::count());
    }
}
