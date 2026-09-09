<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DailySession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Marites Reboredo']);
        Balance::current()->update(['gcash_balance' => 20000, 'cash_balance' => 20000]);

        $this->session = DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 20000,
            'opening_cash_balance' => 20000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now()->subHours(3),
        ]);
    }

    private function txn(array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'daily_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'type' => 'cash_in',
            'amount' => 1500,
            'service_charge' => 20,
            'charge_paid_in' => 'cash',
            'mobile_number' => '09171234567',
            'reference_number' => 'REF-'.uniqid(),
        ], $overrides));
    }

    private function csv($response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    public function test_transactions_export_contains_the_rows(): void
    {
        $this->txn(['reference_number' => 'EXPORT-ME-1']);

        $response = $this->actingAs($this->user)->get(route('export.transactions'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->csv($response);

        $this->assertStringContainsString('EXPORT-ME-1', $csv);
        $this->assertStringContainsString('09171234567', $csv);
        $this->assertStringContainsString('Marites Reboredo', $csv);
        $this->assertStringContainsString('1500.00', $csv);
        $this->assertStringContainsString('Cash In', $csv);
    }

    /** Excel shows mojibake for UTF-8 without a byte order mark. */
    public function test_the_csv_starts_with_a_utf8_bom(): void
    {
        $this->txn();

        $csv = $this->csv($this->actingAs($this->user)->get(route('export.transactions')));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    /** The export must match what the page is showing, not dump the whole table. */
    public function test_the_export_respects_the_page_filters(): void
    {
        $this->txn(['type' => 'cash_in', 'reference_number' => 'THE-CASH-IN']);
        $this->txn(['type' => 'cash_out', 'reference_number' => 'THE-CASH-OUT']);

        $csv = $this->csv(
            $this->actingAs($this->user)->get(route('export.transactions', ['type' => 'cash_out']))
        );

        $this->assertStringContainsString('THE-CASH-OUT', $csv);
        $this->assertStringNotContainsString('THE-CASH-IN', $csv);
    }

    public function test_sessions_export_reports_duration_and_totals(): void
    {
        $this->txn(['service_charge' => 20]);
        $this->txn(['service_charge' => 15]);

        $csv = $this->csv(
            $this->actingAs($this->user)->get(route('export.sessions', ['range' => 'all']))
        );

        $this->assertStringContainsString('3h 0m', $csv);
        $this->assertStringContainsString('35.00', $csv);   // earnings
        $this->assertStringContainsString('Active', $csv);
    }

    /** An auto-closed day was never counted by hand — the export must say so. */
    public function test_an_auto_closed_session_is_labelled_in_the_export(): void
    {
        $this->session->close(19000, 0, auto: true);

        $csv = $this->csv(
            $this->actingAs($this->user)->get(route('export.sessions', ['range' => 'all']))
        );

        $this->assertStringContainsString('Auto-closed', $csv);
    }

    public function test_exports_require_authentication(): void
    {
        $this->get(route('export.transactions'))->assertRedirect(route('login'));
        $this->get(route('export.sessions'))->assertRedirect(route('login'));
    }

    public function test_an_invalid_filter_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->get(route('export.transactions', ['type' => 'nonsense']))
            ->assertSessionHasErrors('type');
    }
}
