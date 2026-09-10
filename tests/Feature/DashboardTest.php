<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_the_dashboard_renders_with_no_session_open(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Day not started')
            ->assertSee('Start day')
            ->assertSee('Start the day to record transactions.');
    }

    public function test_the_dashboard_shows_the_live_balances_and_session_totals(): void
    {
        Balance::current()->update(['gcash_balance' => 7500, 'cash_balance' => 2250]);

        $session = DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 8000,
            'opening_cash_balance' => 2000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);

        Transaction::create([
            'daily_session_id' => $session->id,
            'user_id' => $this->user->id,
            'type' => 'cash_in',
            'amount' => 500,
            'service_charge' => 10,
            'charge_paid_in' => 'cash',
            'reference_number' => 'DASH-REF-1',
        ]);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('₱7,500.00')           // GCash balance (centavos in their own span)
            ->assertSeeText('₱2,250.00')           // cash balance
            ->assertSee('Opened at ₱8,000.00')     // opening figure under the balance
            ->assertSee('DASH-REF-1')              // recent transactions list
            ->assertSee('End day');
    }

    /**
     * The modal builds its rate guide and live preview from the config the
     * server charges from, so the brackets must reach the page.
     */
    public function test_the_dashboard_passes_the_service_charge_brackets_to_the_modal(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('chargeBrackets', config('gtrack.service_charge_brackets'))
            ->assertSee('transactionModal(', escape: false);
    }

    public function test_the_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
