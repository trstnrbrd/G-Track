<?php

namespace Tests\Feature;

use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** The default range is today, so older rows must not leak in. */
    public function test_history_respects_the_date_range(): void
    {
        $old = $this->txn(['reference_number' => 'LAST-MONTH']);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        $this->actingAs($this->user)->get(route('history'))
            ->assertOk()
            ->assertDontSee('LAST-MONTH');

        $this->actingAs($this->user)->get(route('history', ['range' => 'all']))
            ->assertOk()
            ->assertSee('LAST-MONTH');
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
