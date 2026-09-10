<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DailySession $session;

    /** The data each PDF view was rendered with, captured as it rendered. */
    private array $rendered = [];

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

        // A PDF can't be searched for text here: with font subsetting, letters
        // are stored as glyph numbers, not characters. So capture the data each
        // PDF is built from as it renders — these are exactly the rows it lays out.
        View::composer(['exports.transactions', 'exports.sessions'], function ($view) {
            $this->rendered[$view->name()] = $view->getData();
        });
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

    /** Re-renders a captured PDF view as HTML, so its wording can be checked. */
    private function html(string $view): string
    {
        return view($view, $this->rendered[$view])->render();
    }

    public function test_the_transactions_export_is_a_pdf_download(): void
    {
        $this->txn();

        $response = $this->actingAs($this->user)->get(route('export.transactions'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('gtrack-transactions-'.now()->format('Y-m-d').'.pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /** The PDF must match what the History page is showing, not the whole table. */
    public function test_the_export_lists_exactly_what_the_page_filters_show(): void
    {
        $this->txn(['type' => 'cash_in', 'reference_number' => 'THE-CASH-IN']);
        $this->txn(['type' => 'cash_out', 'reference_number' => 'THE-CASH-OUT']);

        $this->actingAs($this->user)
            ->get(route('export.transactions', ['type' => 'cash_out']))
            ->assertOk();

        $this->assertSame(
            ['THE-CASH-OUT'],
            $this->rendered['exports.transactions']['rows']->pluck('reference_number')->all(),
        );
    }

    /** Past the cap the table stops — but a total that stopped too would be wrong. */
    public function test_totals_cover_every_row_even_when_the_table_is_capped(): void
    {
        config(['gtrack.export_max_rows' => 3]);

        foreach (range(1, 5) as $i) {
            $this->txn(['amount' => 1000, 'service_charge' => 15]);
        }

        $this->actingAs($this->user)->get(route('export.transactions'))->assertOk();

        $data = $this->rendered['exports.transactions'];
        $this->assertCount(3, $data['rows']);
        $this->assertSame(5, $data['summary']['count']);
        $this->assertEquals(5000.00, $data['summary']['cash_in']);
        $this->assertEquals(75.00, $data['summary']['service_charge']);
    }

    public function test_a_capped_pdf_says_so(): void
    {
        config(['gtrack.export_max_rows' => 3]);

        foreach (range(1, 5) as $i) {
            $this->txn();
        }

        $this->actingAs($this->user)->get(route('export.transactions'));

        $this->assertStringContainsString('Showing the first 3 of 5 transactions', $this->html('exports.transactions'));
    }

    /**
     * dompdf's default font (Helvetica) has no peso sign, so every amount would
     * print as "?500.00". This guards the one line that prevents it.
     */
    public function test_the_pdf_uses_a_font_that_has_the_peso_sign(): void
    {
        $this->txn();

        $this->actingAs($this->user)->get(route('export.transactions'));

        $this->assertStringContainsString('font-family: "DejaVu Sans"', $this->html('exports.transactions'));
    }

    public function test_the_pdf_header_says_what_it_covers(): void
    {
        $this->txn();

        $this->actingAs($this->user)->get(route('export.transactions', ['type' => 'cash_in', 'search' => '0917']));

        $this->assertSame(
            'Today · '.now()->format('M j, Y').' · Cash in only · Matching "0917"',
            $this->rendered['exports.transactions']['scope'],
        );
    }

    public function test_the_sessions_export_is_a_pdf_download(): void
    {
        $response = $this->actingAs($this->user)->get(route('export.sessions', ['range' => 'all']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('gtrack-daily-balances-'.now()->format('Y-m-d').'.pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_the_sessions_export_totals_earnings_and_transactions(): void
    {
        $this->txn(['service_charge' => 20]);
        $this->txn(['service_charge' => 15]);

        $this->actingAs($this->user)->get(route('export.sessions', ['range' => 'all']));

        $totals = $this->rendered['exports.sessions']['totals'];
        $this->assertSame(1, $totals['days']);
        $this->assertSame(2, $totals['transactions']);
        $this->assertEquals(35.00, $totals['earned']);
    }

    /** An auto-closed day was never counted by hand — a printed report must say so. */
    public function test_an_auto_closed_day_is_labelled_in_the_pdf(): void
    {
        $this->session->close(19000, 0, auto: true);

        $this->actingAs($this->user)->get(route('export.sessions', ['range' => 'all']));

        $html = $this->html('exports.sessions');
        $this->assertStringContainsString('Auto-closed', $html);
        $this->assertStringContainsString('never counted by hand', $html);
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
