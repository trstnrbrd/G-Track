<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\CapitalEntry;
use App\Models\DailySession;
use App\Models\Setting;
use App\Models\StoreSale;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DatabaseBackup;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Marites Owner', 'role' => User::ROLE_OWNER]);
        $this->staff = User::factory()->create(['name' => 'Papa Staff', 'role' => User::ROLE_STAFF]);
    }

    private function brackets(array $rows): array
    {
        return array_map(fn (array $r) => ['up_to' => $r[0], 'fee' => $r[1]], $rows);
    }

    private function saveRates(array $rows)
    {
        return $this->actingAs($this->owner)->put(route('settings.shop.rates'), ['brackets' => $this->brackets($rows)]);
    }

    private function openDay(float $gcash = 10000, float $cash = 10000): DailySession
    {
        Balance::current()->update(['gcash_balance' => $gcash, 'cash_balance' => $cash]);

        return DailySession::create([
            'user_id' => $this->owner->id,
            'opening_gcash_balance' => $gcash,
            'opening_cash_balance' => $cash,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);
    }

    // ---- access ---------------------------------------------------------------------

    public function test_staff_are_refused_every_owner_setting(): void
    {
        $this->actingAs($this->staff)->put(route('settings.shop.rates'), ['brackets' => $this->brackets([[null, 99]])])->assertForbidden();
        $this->actingAs($this->staff)->put(route('settings.shop.quick-amounts'), ['quick_amounts' => '1, 2'])->assertForbidden();
        $this->actingAs($this->staff)->put(route('settings.shop.auto-close'), ['hours' => 5])->assertForbidden();
        $this->actingAs($this->staff)->delete(route('settings.shop.reset', 'rates'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('settings.backup'))->assertForbidden();

        $this->assertSame(0, Setting::count());
    }

    public function test_only_the_owner_sees_the_shop_section(): void
    {
        $this->actingAs($this->owner)->get(route('settings'))
            ->assertOk()
            ->assertSee('Service charge rates')
            ->assertSee('Download backup');

        $this->actingAs($this->staff)->get(route('settings'))
            ->assertOk()
            ->assertDontSee('Service charge rates')
            ->assertDontSee('Download backup');
    }

    // ---- service charge rates --------------------------------------------------------

    public function test_the_defaults_apply_until_the_owner_changes_them(): void
    {
        $this->assertSame(config('gtrack.service_charge_brackets'), ShopSettings::serviceChargeBrackets());
        $this->assertEquals(10.0, Transaction::serviceChargeFor(500));
    }

    public function test_new_rates_are_used_for_new_transactions_and_leave_old_ones_alone(): void
    {
        $this->openDay();

        // One recorded at the old rate: ₱500 → ₱10.
        $this->actingAs($this->owner)->post(route('transactions.store'), [
            'type' => 'cash_in', 'amount' => 500, 'charge_paid_in' => 'cash', 'mobile_number' => '09171234567',
        ])->assertRedirect();

        $this->saveRates([[500, 15], [1000, 20], [null, 120]])->assertSessionHasNoErrors();

        $this->assertEquals([['up_to' => 500, 'fee' => 15], ['up_to' => 1000, 'fee' => 20], ['up_to' => null, 'fee' => 120]], ShopSettings::serviceChargeBrackets());
        $this->assertEquals(15.0, Transaction::serviceChargeFor(500));
        $this->assertEquals(120.0, Transaction::serviceChargeFor(5000));

        // A new one is charged at the new rate...
        $this->actingAs($this->owner)->post(route('transactions.store'), [
            'type' => 'cash_in', 'amount' => 500, 'charge_paid_in' => 'cash', 'mobile_number' => '09170000001',
        ])->assertRedirect();

        $fees = Transaction::orderBy('id')->pluck('service_charge')->map(fn ($f) => (float) $f)->all();
        $this->assertSame([10.0, 15.0], $fees, 'The first transaction keeps the fee it was recorded with.');
    }

    public function test_the_cash_modal_is_given_the_new_rates(): void
    {
        $this->saveRates([[300, 8], [null, 40]]);

        $this->actingAs($this->owner)->get(route('dashboard'))
            ->assertViewHas('chargeBrackets', fn (array $b) => $b === [['up_to' => 300, 'fee' => 8], ['up_to' => null, 'fee' => 40]]);
    }

    public function test_the_last_row_is_always_the_catch_all(): void
    {
        // Even if a limit is typed on the last row, it's ignored: nothing may
        // be left without a fee.
        $this->saveRates([[500, 10], [900, 99]])->assertSessionHasNoErrors();

        $this->assertNull(ShopSettings::serviceChargeBrackets()[1]['up_to']);
        $this->assertEquals(99.0, Transaction::serviceChargeFor(1000000));
    }

    public function test_bad_rates_are_refused_and_nothing_is_saved(): void
    {
        // Limits must rise.
        $this->saveRates([[1000, 15], [500, 10], [null, 50]])->assertSessionHasErrors('brackets', errorBag: 'rates');
        // A fee is required.
        $this->saveRates([[500, null], [null, 50]])->assertSessionHasErrors(errorBag: 'rates');
        // A fee can't be negative.
        $this->saveRates([[500, -1], [null, 50]])->assertSessionHasErrors(errorBag: 'rates');
        // A middle row needs its limit.
        $this->saveRates([[null, 10], [null, 50]])->assertSessionHasErrors(errorBag: 'rates');
        // Not a list at all.
        $this->actingAs($this->owner)->put(route('settings.shop.rates'), [])->assertSessionHasErrors('brackets', errorBag: 'rates');

        $this->assertFalse(ShopSettings::isCustom(ShopSettings::BRACKETS));
        $this->assertSame(config('gtrack.service_charge_brackets'), ShopSettings::serviceChargeBrackets());
    }

    public function test_resetting_puts_the_shipped_rates_back(): void
    {
        $this->saveRates([[100, 1], [null, 2]]);
        $this->assertTrue(ShopSettings::isCustom(ShopSettings::BRACKETS));

        $this->actingAs($this->owner)->delete(route('settings.shop.reset', 'rates'))->assertRedirect();

        $this->assertFalse(ShopSettings::isCustom(ShopSettings::BRACKETS));
        $this->assertSame(config('gtrack.service_charge_brackets'), ShopSettings::serviceChargeBrackets());
    }

    public function test_an_unknown_setting_cannot_be_reset(): void
    {
        $this->actingAs($this->owner)->delete(route('settings.shop.reset', 'users'))->assertNotFound();
    }

    /** A damaged row must never be able to break recording a transaction. */
    public function test_a_corrupt_stored_value_falls_back_to_the_default(): void
    {
        Setting::put(ShopSettings::BRACKETS, [['up_to' => 900, 'fee' => 5], ['up_to' => 100, 'fee' => 6], ['up_to' => null, 'fee' => 7]], null);
        Setting::put(ShopSettings::QUICK_AMOUNTS, ['lots', 'of', 'text'], null);
        Setting::put(ShopSettings::AUTO_CLOSE_HOURS, 9999, null);

        $this->assertSame(config('gtrack.service_charge_brackets'), ShopSettings::serviceChargeBrackets());
        $this->assertSame(config('gtrack.quick_amounts'), ShopSettings::quickAmounts());
        $this->assertSame((int) config('gtrack.auto_close_after_hours'), ShopSettings::autoCloseAfterHours());
    }

    // ---- quick amounts -----------------------------------------------------------------

    public function test_quick_amounts_are_saved_sorted_and_reach_the_form(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.shop.quick-amounts'), ['quick_amounts' => '2000, 100  500'])
            ->assertSessionHasNoErrors();

        $this->assertSame([100, 500, 2000], ShopSettings::quickAmounts());

        $this->actingAs($this->owner)->get(route('dashboard'))
            ->assertViewHas('quickAmounts', [100, 500, 2000]);
    }

    public function test_bad_quick_amounts_are_refused(): void
    {
        foreach (['', 'abc', '100, 100', '0, 100', '1, 2, 3, 4, 5, 6, 7, 8, 9', '5000000'] as $bad) {
            $this->actingAs($this->owner)
                ->put(route('settings.shop.quick-amounts'), ['quick_amounts' => $bad])
                ->assertSessionHasErrors(errorBag: 'quickAmounts');
        }

        $this->assertFalse(ShopSettings::isCustom(ShopSettings::QUICK_AMOUNTS));
    }

    // ---- auto-close ------------------------------------------------------------------------

    public function test_the_auto_close_cutoff_is_the_one_the_owner_chose(): void
    {
        $session = DailySession::create([
            'user_id' => $this->owner->id,
            'opening_gcash_balance' => 1000,
            'opening_cash_balance' => 1000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now()->subHours(11),
        ]);

        // The default is 16 hours, so an 11-hour-old day is left alone.
        $this->artisan('gtrack:close-stale-sessions')->assertSuccessful();
        $this->assertTrue($session->fresh()->isActive());

        $this->actingAs($this->owner)->put(route('settings.shop.auto-close'), ['hours' => 10])->assertSessionHasNoErrors();
        $this->assertSame(10, ShopSettings::autoCloseAfterHours());

        $this->artisan('gtrack:close-stale-sessions')->assertSuccessful();
        $this->assertFalse($session->fresh()->isActive());
    }

    public function test_bad_auto_close_hours_are_refused(): void
    {
        foreach (['0', '49', '-3', 'ten', '', '1.5'] as $bad) {
            $this->actingAs($this->owner)
                ->put(route('settings.shop.auto-close'), ['hours' => $bad])
                ->assertSessionHasErrors('hours', errorBag: 'autoClose');
        }

        $this->assertFalse(ShopSettings::isCustom(ShopSettings::AUTO_CLOSE_HOURS));
    }

    // ---- backup ------------------------------------------------------------------------------

    private function seedSomeData(): void
    {
        $session = $this->openDay(gcash: 5000, cash: 3000);

        Transaction::create([
            'daily_session_id' => $session->id, 'user_id' => $this->owner->id, 'type' => 'cash_in',
            'mobile_number' => '09171234567', 'reference_number' => "O'Brien \"ref\" ñ-1",
            'amount' => 1000, 'service_charge' => 15, 'charge_paid_in' => 'cash',
        ]);
        StoreSale::create(['daily_session_id' => $session->id, 'user_id' => $this->owner->id, 'amount' => 250.5, 'note' => 'rice; "1 sack"']);
        CapitalEntry::create(['user_id' => $this->owner->id, 'kind' => 'capital', 'amount' => 75000]);
    }

    public function test_the_backup_downloads_as_sql_with_the_shop_data_and_notes_the_time(): void
    {
        $this->seedSomeData();

        $response = $this->actingAs($this->owner)->get(route('settings.backup'))->assertOk();

        $this->assertStringStartsWith('application/sql', $response->headers->get('content-type'));
        $this->assertStringContainsString('gtrack-backup-', $response->headers->get('content-disposition'));

        $sql = $response->streamedContent();
        $this->assertStringContainsString('INSERT INTO `transactions`', $sql);
        $this->assertStringContainsString('INSERT INTO `store_sales`', $sql);
        $this->assertStringContainsString('INSERT INTO `capital_entries`', $sql);
        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        // Temporary tables are not in it.
        $this->assertStringNotContainsString('INSERT INTO `sessions`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `cache`', $sql);

        $last = Setting::where('key', ShopSettings::LAST_BACKUP)->firstOrFail()->value;
        $this->assertSame('download', $last['via']);
        $this->assertGreaterThan(0, $last['rows']);
    }

    /** The point of a backup: it must actually bring the data back. */
    public function test_a_backup_restores_exactly_what_was_in_it(): void
    {
        $this->seedSomeData();

        $before = [
            'transactions' => DB::table('transactions')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'store_sales' => DB::table('store_sales')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'capital_entries' => DB::table('capital_entries')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'balances' => DB::table('balances')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'users' => DB::table('users')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        ];

        $sql = DatabaseBackup::toString();

        // Wipe it, as a failed disk would.
        foreach (array_reverse(DatabaseBackup::tables()) as $table) {
            DB::table($table)->delete();
        }
        $this->assertSame(0, DB::table('transactions')->count());

        DB::unprepared($sql);

        foreach ($before as $table => $rows) {
            $after = DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $this->assertEquals($rows, $after, "The {$table} table came back different.");
        }
    }

    public function test_the_nightly_backup_is_written_and_old_ones_are_pruned(): void
    {
        Storage::fake('local');
        $this->seedSomeData();

        // Three older files already there.
        foreach (['2026-01-01-000000', '2026-01-02-000000', '2026-01-03-000000'] as $stamp) {
            Storage::disk('local')->put("backups/gtrack-backup-{$stamp}.sql", '-- old');
        }

        $this->artisan('gtrack:backup', ['--keep' => 2])->assertSuccessful();

        $files = collect(Storage::disk('local')->files('backups'))->sort()->values();
        $this->assertCount(2, $files, 'Only the newest two are kept.');
        $this->assertStringContainsString(now()->format('Y-m-d'), $files->last());
        $this->assertStringContainsString('INSERT INTO `transactions`', Storage::disk('local')->get($files->last()));

        $last = Setting::where('key', ShopSettings::LAST_BACKUP)->firstOrFail()->value;
        $this->assertSame('scheduled', $last['via']);
    }

    public function test_the_page_warns_when_there_has_been_no_backup(): void
    {
        $this->actingAs($this->owner)->get(route('settings'))
            ->assertOk()
            ->assertSee('No backup yet');

        Setting::put(ShopSettings::LAST_BACKUP, ['at' => now()->toIso8601String(), 'via' => 'download', 'rows' => 12], $this->owner->id);

        $this->actingAs($this->owner)->get(route('settings'))
            ->assertOk()
            ->assertDontSee('No backup yet')
            ->assertSee('Last backup:');
    }
}
