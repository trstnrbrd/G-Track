<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\DailySession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DailySession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Balance::current()->update(['gcash_balance' => 7000, 'cash_balance' => 3000]);

        $this->session = DailySession::create([
            'user_id' => $this->user->id,
            'opening_gcash_balance' => 7000,
            'opening_cash_balance' => 3000,
            'status' => DailySession::STATUS_ACTIVE,
            'active_guard' => 1,
            'started_at' => now(),
        ]);
    }

    private function transaction(array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'daily_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'type' => 'cash_in',
            'mobile_number' => '09171234567',
            'reference_number' => null,
            'amount' => 500,
            'service_charge' => 10,
            'charge_paid_in' => 'cash',
        ], $overrides));
    }

    private function edit(Transaction $transaction, array $data)
    {
        return $this->actingAs($this->user)->patch(route('transactions.update', $transaction), $data);
    }

    public function test_a_cash_in_can_have_its_reference_added_afterwards(): void
    {
        $transaction = $this->transaction();

        $this->edit($transaction, [
            'mobile_number' => '09171234567',
            'reference_number' => '1234567890123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('1234567890123', $transaction->fresh()->reference_number);
    }

    public function test_a_cash_out_can_have_its_number_added_afterwards(): void
    {
        $transaction = $this->transaction([
            'type' => 'cash_out',
            'mobile_number' => null,
            'reference_number' => '9876543210123',
        ]);

        $this->edit($transaction, [
            'mobile_number' => '0917 555 0101',
            'reference_number' => '9876543210123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('09175550101', $transaction->fresh()->mobile_number);
    }

    public function test_editing_never_touches_the_money(): void
    {
        $transaction = $this->transaction();

        // A tampered or stale client trying to rewrite how the balances moved.
        $this->edit($transaction, [
            'mobile_number' => '09171234567',
            'reference_number' => 'REF-'.uniqid(),
            'type' => 'cash_out',
            'amount' => 99999,
            'service_charge' => 0,
            'charge_paid_in' => 'gcash',
            'daily_session_id' => 999,
        ])->assertRedirect();

        $fresh = $transaction->fresh();
        $this->assertSame('cash_in', $fresh->type);
        $this->assertEquals(500.00, $fresh->amount);
        $this->assertEquals(10.00, $fresh->service_charge);
        $this->assertSame('cash', $fresh->charge_paid_in);
        $this->assertSame($this->session->id, $fresh->daily_session_id);

        $balance = Balance::current()->fresh();
        $this->assertEquals(7000.00, $balance->gcash_balance);
        $this->assertEquals(3000.00, $balance->cash_balance);
    }

    public function test_a_cash_in_cannot_lose_its_number(): void
    {
        $transaction = $this->transaction();

        $this->edit($transaction, ['mobile_number' => '', 'reference_number' => '1234567890123'])
            ->assertSessionHasErrors('mobile_number', errorBag: 'editTransaction');

        $this->assertSame('09171234567', $transaction->fresh()->mobile_number);
    }

    public function test_a_cash_out_cannot_lose_its_reference(): void
    {
        $transaction = $this->transaction([
            'type' => 'cash_out',
            'mobile_number' => null,
            'reference_number' => '9876543210123',
        ]);

        $this->edit($transaction, ['mobile_number' => '09171234567', 'reference_number' => ''])
            ->assertSessionHasErrors('reference_number', errorBag: 'editTransaction');

        $this->assertSame('9876543210123', $transaction->fresh()->reference_number);
    }

    public function test_a_malformed_number_is_rejected(): void
    {
        $transaction = $this->transaction();

        $this->edit($transaction, ['mobile_number' => '0917123', 'reference_number' => null])
            ->assertSessionHasErrors('mobile_number', errorBag: 'editTransaction');

        $this->assertSame('09171234567', $transaction->fresh()->mobile_number);
    }

    public function test_a_reference_already_on_another_transaction_is_rejected(): void
    {
        $this->transaction(['reference_number' => '1111111111111']);
        $second = $this->transaction(['mobile_number' => '09170000000']);

        $this->edit($second, ['mobile_number' => '09170000000', 'reference_number' => '1111111111111'])
            ->assertSessionHasErrors('reference_number', errorBag: 'editTransaction');

        $this->assertNull($second->fresh()->reference_number);
    }

    public function test_saving_a_transactions_own_reference_again_is_fine(): void
    {
        $transaction = $this->transaction(['reference_number' => '1111111111111']);

        $this->edit($transaction, ['mobile_number' => '09179998888', 'reference_number' => '1111111111111'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('09179998888', $transaction->fresh()->mobile_number);
    }

    public function test_a_failed_edit_reopens_the_modal_with_the_error_on_the_page(): void
    {
        $this->transaction(['reference_number' => '1111111111111']);
        $second = $this->transaction(['mobile_number' => '09170000000']);

        $this->actingAs($this->user)
            ->from(route('history', ['range' => 'all']))
            ->patch(route('transactions.update', $second), [
                'transaction_id' => $second->id,
                'mobile_number' => '09170000000',
                'reference_number' => '1111111111111',
            ])
            ->assertRedirect(route('history', ['range' => 'all']));

        $this->get(route('history', ['range' => 'all']))
            ->assertOk()
            ->assertSee('This reference number is already recorded');
    }

    public function test_guests_cannot_edit(): void
    {
        $transaction = $this->transaction();

        $this->patch(route('transactions.update', $transaction), [
            'mobile_number' => '09171234567',
            'reference_number' => '1234567890123',
        ])->assertRedirect(route('login'));

        $this->assertNull($transaction->fresh()->reference_number);
    }
}
