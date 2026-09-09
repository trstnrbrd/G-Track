<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseStaleSessionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Balance::current()->update(['gcash_balance' => 5000, 'cash_balance' => 3000]);
    }

    private function openSessionStartedHoursAgo(int $hours): DailySession
    {
        return DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 5000,
            'opening_cash_balance' => 3000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now()->subHours($hours),
        ]);
    }

    public function test_a_session_open_past_the_cutoff_is_closed(): void
    {
        $session = $this->openSessionStartedHoursAgo(20);

        $this->artisan('gtrack:close-stale-sessions')->assertSuccessful();

        $session->refresh();
        $this->assertFalse($session->isActive());
        $this->assertTrue((bool) $session->auto_closed, 'must be flagged as auto-closed, not a real count');
        $this->assertNotNull($session->ended_at);
    }

    /** A quiet trading day must never be closed out from under the operator. */
    public function test_a_session_within_the_cutoff_is_left_alone(): void
    {
        $session = $this->openSessionStartedHoursAgo(8);

        $this->artisan('gtrack:close-stale-sessions')->assertSuccessful();

        $this->assertTrue($session->refresh()->isActive());
    }

    public function test_auto_closing_applies_the_same_balance_rules_as_end_day(): void
    {
        $this->openSessionStartedHoursAgo(20);

        $this->artisan('gtrack:close-stale-sessions');

        $balance = Balance::current()->fresh();
        $this->assertEquals(5000.00, $balance->gcash_balance, 'GCash float carries over');
        $this->assertEquals(0.00, $balance->cash_balance, 'cash leaves the drawer');
    }

    /** Closing must release the guard so tomorrow can start normally. */
    public function test_a_new_day_can_start_after_an_auto_close(): void
    {
        $this->openSessionStartedHoursAgo(20);
        $this->artisan('gtrack:close-stale-sessions');

        $this->actingAs($this->user)
            ->post(route('day.start'), ['starting_gcash' => 5000, 'starting_cash' => 1000])
            ->assertSessionHas('swal.icon', 'success');

        $this->assertSame(1, DailySession::where('status', 'active')->count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $session = $this->openSessionStartedHoursAgo(20);

        $this->artisan('gtrack:close-stale-sessions', ['--dry-run' => true])->assertSuccessful();

        $this->assertTrue($session->refresh()->isActive());
    }

    public function test_it_is_safe_to_run_with_no_open_session(): void
    {
        $this->artisan('gtrack:close-stale-sessions')->assertSuccessful();

        $this->assertSame(0, DailySession::count());
    }

    /** A day ended by a human must not be mislabelled as auto-closed. */
    public function test_a_manually_ended_day_is_not_flagged_as_auto_closed(): void
    {
        $this->openSessionStartedHoursAgo(2);

        $this->actingAs($this->user)->post(route('day.end'));

        $this->assertFalse((bool) DailySession::sole()->auto_closed);
    }
}
