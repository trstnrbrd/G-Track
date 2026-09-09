<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A GCash reference identifies one real transfer. Recording it twice moves the
 * balances twice for money that only moved once — the exact kind of error that
 * is invisible until closing time.
 */
class DuplicateReferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Balance::current()->update(['gcash_balance' => 50000, 'cash_balance' => 50000]);

        DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 50000,
            'opening_cash_balance' => 50000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);
    }

    private function record(array $overrides = [])
    {
        return $this->actingAs($this->user)->post(route('transactions.store'), array_merge([
            'type' => 'cash_out',
            'amount' => 500,
            'charge_paid_in' => 'cash',
            'reference_number' => 'GC-1234567890',
        ], $overrides));
    }

    public function test_the_same_reference_cannot_be_recorded_twice(): void
    {
        $this->record()->assertRedirect()->assertSessionHasNoErrors();

        $this->record()->assertSessionHasErrors('reference_number');

        $this->assertSame(1, Transaction::count());
    }

    public function test_the_balances_do_not_move_on_a_rejected_duplicate(): void
    {
        $this->record();

        $afterFirst = Balance::current()->fresh();

        $this->record();

        $afterDuplicate = Balance::current()->fresh();

        $this->assertEquals($afterFirst->gcash_balance, $afterDuplicate->gcash_balance);
        $this->assertEquals($afterFirst->cash_balance, $afterDuplicate->cash_balance);
    }

    public function test_a_duplicate_is_rejected_even_across_transaction_types(): void
    {
        $this->record(['type' => 'cash_out', 'reference_number' => 'SHARED-REF']);

        $this->record([
            'type' => 'cash_in',
            'reference_number' => 'SHARED-REF',
            'mobile_number' => '09171234567',
        ])->assertSessionHasErrors('reference_number');

        $this->assertSame(1, Transaction::count());
    }

    /** Cash-ins are often recorded without a reference — those must not collide. */
    public function test_many_transactions_can_have_no_reference_at_all(): void
    {
        foreach (range(1, 3) as $i) {
            $this->record([
                'type' => 'cash_in',
                'reference_number' => null,
                'mobile_number' => '09171234567',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Transaction::count());
    }

    /** The database is the real guard, so it must hold even if validation is bypassed. */
    public function test_the_database_itself_refuses_a_duplicate(): void
    {
        $this->record(['reference_number' => 'DB-LEVEL-REF']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Transaction::create([
            'daily_session_id' => DailySession::active()->id,
            'user_id' => $this->user->id,
            'type' => 'cash_out',
            'amount' => 100,
            'service_charge' => 10,
            'charge_paid_in' => 'cash',
            'reference_number' => 'DB-LEVEL-REF',
        ]);
    }
}
