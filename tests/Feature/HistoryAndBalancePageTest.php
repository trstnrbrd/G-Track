<?php

namespace Tests\Feature;

use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HistoryAndBalancePageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DailySession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->session = DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 5000,
            'opening_cash_balance' => 3000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);
    }

    private function txn(array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'daily_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'type' => 'cash_in',
            'amount' => 1000,
            'service_charge' => 15,
            'charge_paid_in' => 'cash',
            'mobile_number' => '09171234567',
            // Unique by default: reference_number now carries a unique index,
            // so a fixed fixture value collides on the second row.
            'reference_number' => 'REF-'.uniqid(),
        ], $overrides));
    }

    public function test_history_lists_todays_transactions(): void
    {
        $this->txn(['reference_number' => 'REF-VISIBLE']);

        $this->actingAs($this->user)->get(route('history'))
            ->assertOk()
            ->assertSee('REF-VISIBLE')
            ->assertSee('09171234567');
    }

    public function test_history_can_filter_by_type(): void
    {
        $this->txn(['type' => 'cash_in', 'reference_number' => 'THE-CASH-IN']);
        $this->txn(['type' => 'cash_out', 'reference_number' => 'THE-CASH-OUT']);

        $this->actingAs($this->user)->get(route('history', ['type' => 'cash_out']))
            ->assertOk()
            ->assertSee('THE-CASH-OUT')
            ->assertDontSee('THE-CASH-IN');
    }

    public function test_history_can_search_by_reference_or_mobile(): void
    {
        $this->txn(['reference_number' => 'FINDME-123', 'mobile_number' => '09171111111']);
        $this->txn(['reference_number' => 'OTHER-999', 'mobile_number' => '09172222222']);

        $this->actingAs($this->user)->get(route('history', ['search' => 'FINDME']))
            ->assertOk()
            ->assertSee('FINDME-123')
            ->assertDontSee('OTHER-999');

        $this->actingAs($this->user)->get(route('history', ['search' => '09172222222']))
            ->assertOk()
            ->assertSee('OTHER-999')
            ->assertDontSee('FINDME-123');
    }

    /**
     * Nothing is hidden by default — older rows are right there — and picking a
     * narrower range still narrows it.
     */
    public function test_history_shows_older_rows_by_default_and_respects_a_chosen_range(): void
    {
        $old = $this->txn(['reference_number' => 'LAST-MONTH']);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();
        $this->txn(['reference_number' => 'THIS-ONE-IS-TODAY']);

        $this->actingAs($this->user)->get(route('history'))
            ->assertOk()
            ->assertSee('LAST-MONTH')
            ->assertSee('THIS-ONE-IS-TODAY');

        $this->actingAs($this->user)->get(route('history', ['range' => 'today']))
            ->assertOk()
            ->assertDontSee('LAST-MONTH')
            ->assertSee('THIS-ONE-IS-TODAY');
    }

    /**
     * Pagination must follow every filter: the page links keep the active
     * filters, and each filter's own total is what is counted.
     */
    public function test_pagination_follows_every_filter(): void
    {
        foreach (range(1, 30) as $i) {
            $this->txn(['type' => 'cash_in', 'reference_number' => 'IN-'.$i]);
        }
        foreach (range(1, 28) as $i) {
            $this->txn(['type' => 'cash_out', 'reference_number' => 'OUT-'.$i]);
        }

        // No filter: 58 rows over 3 pages of 25.
        $this->actingAs($this->user)->get(route('history'))
            ->assertSeeText('Showing 1–25 of 58')
            ->assertSee('page=3', false);

        // Type filter: 30 cash ins — and the links keep type=cash_in.
        $this->actingAs($this->user)->get(route('history', ['type' => 'cash_in']))
            ->assertSeeText('Showing 1–25 of 30')
            ->assertSee('type=cash_in', false)
            ->assertSee('page=2', false);

        $this->actingAs($this->user)->get(route('history', ['type' => 'cash_in', 'page' => 2]))
            ->assertSeeText('Showing 26–30 of 30')
            ->assertSee('IN-5')      // newest first, so page 2 holds the oldest five
            ->assertDontSee('IN-30') // that one is on page 1
            ->assertDontSee('OUT-');

        // Search: one page, still told how many there are, no page links.
        $this->actingAs($this->user)->get(route('history', ['search' => 'OUT-1']))
            ->assertSeeText('Showing 1–11 of 11')
            ->assertDontSee('page=2', false);

        // Date ranges and a custom range count their own rows.
        foreach (['today', 'week', 'month', 'last30', 'all'] as $range) {
            $this->actingAs($this->user)->get(route('history', ['range' => $range]))
                ->assertSeeText('Showing 1–25 of 58');
        }

        $this->actingAs($this->user)
            ->get(route('history', ['range' => 'custom', 'from' => today()->toDateString(), 'to' => today()->toDateString()]))
            ->assertSeeText('Showing 1–25 of 58')
            ->assertSee('range=custom', false);
    }

    public function test_a_single_page_still_says_how_many_there_are(): void
    {
        $this->txn();
        $this->txn();

        $this->actingAs($this->user)->get(route('history'))
            ->assertSeeText('Showing 1–2 of 2');
    }

    /**
     * The reported bug: on a Monday morning "This Week" came up empty even
     * though yesterday (Sunday) had entries, because the week began on Monday.
     */
    public function test_the_week_range_is_the_last_seven_days_not_monday_to_now(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00'); // a Monday
        $this->assertTrue(now()->isMonday());

        $sunday = $this->txn(['reference_number' => 'SUNDAY-ROW']);
        $sunday->forceFill(['created_at' => '2026-10-11 20:30:00'])->save();
        $tooOld = $this->txn(['reference_number' => 'EIGHT-DAYS-AGO']);
        $tooOld->forceFill(['created_at' => '2026-10-04 12:00:00'])->save();

        $this->actingAs($this->user)->get(route('history', ['range' => 'week']))
            ->assertOk()
            ->assertSee('SUNDAY-ROW')
            ->assertDontSee('EIGHT-DAYS-AGO');

        $this->actingAs($this->user)->get(route('history', ['range' => 'last30']))
            ->assertSee('SUNDAY-ROW')
            ->assertSee('EIGHT-DAYS-AGO');

        $this->actingAs($this->user)->get(route('history', ['range' => 'month']))
            ->assertSee('SUNDAY-ROW');

        Carbon::setTestNow();
    }

    /**
     * The reported bug: after End Day (and the next morning's Start Day), the
     * earlier transactions must still be in History and in Recent.
     */
    public function test_transactions_survive_end_day_and_the_next_day(): void
    {
        $yesterday = $this->txn(['reference_number' => 'FROM-YESTERDAY']);
        $yesterday->forceFill(['created_at' => now()->subDay()])->save();
        $this->txn(['reference_number' => 'FROM-TODAY-BEFORE-END']);

        $this->actingAs($this->user)->post(route('day.end'))->assertRedirect();

        // Right after End Day: no session is open, but nothing has gone anywhere.
        $this->actingAs($this->user)->get(route('history'))
            ->assertSee('FROM-YESTERDAY')
            ->assertSee('FROM-TODAY-BEFORE-END');

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertSee('FROM-TODAY-BEFORE-END', false)
            ->assertViewHas('recent', fn ($recent) => $recent->count() === 2);

        // And a new day starts without taking them away.
        $this->actingAs($this->user)
            ->post(route('day.start'), ['starting_gcash' => 5000, 'starting_cash' => 3000])
            ->assertRedirect();

        $this->actingAs($this->user)->get(route('history'))
            ->assertSee('FROM-YESTERDAY')
            ->assertSee('FROM-TODAY-BEFORE-END');
    }

    /**
     * Totals must cover the whole filtered set, not just the visible page —
     * a total that only counted page 1 would be worse than no total at all.
     */
    public function test_history_totals_cover_every_matching_row_not_just_the_page(): void
    {
        // 30 rows at ₱1,000 with a ₱15 charge, across two pages of 25.
        for ($i = 0; $i < 30; $i++) {
            $this->txn(['reference_number' => 'REF-'.$i]);
        }

        $response = $this->actingAs($this->user)->get(route('history'))->assertOk();

        $response->assertSee('₱30,000.00');  // total cash in
        $response->assertSee('₱450.00');     // total service charges
    }

    public function test_an_invalid_filter_is_rejected(): void
    {
        $this->actingAs($this->user)->get(route('history', ['type' => 'nonsense']))
            ->assertSessionHasErrors('type');
    }

    public function test_balance_page_lists_sessions_with_their_earnings(): void
    {
        $this->txn(['service_charge' => 15]);
        $this->txn(['service_charge' => 20]);

        $this->actingAs($this->user)->get(route('balance'))
            ->assertOk()
            ->assertSee('Daily Balance Summary')
            ->assertSee('₱35.00')   // earnings for the session
            ->assertSee('Active');
    }

    public function test_both_pages_require_authentication(): void
    {
        $this->get(route('history'))->assertRedirect(route('login'));
        $this->get(route('balance'))->assertRedirect(route('login'));
    }
}
