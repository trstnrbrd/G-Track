<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'gcash_balance',
        'cash_balance',
    ];

    protected $casts = [
        'gcash_balance' => 'decimal:2',
        'cash_balance' => 'decimal:2',
    ];

    /**
     * The single balances row, creating it if it doesn't exist yet.
     *
     * The shop has one till, so there is exactly one row. If this ever becomes
     * per-staff, this is the method to scope.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'gcash_balance' => 0,
            'cash_balance' => 0,
        ]);
    }

    /**
     * The balances row, locked for the remainder of the surrounding database
     * transaction. Use this — not current() — whenever you are about to change
     * a balance, so two simultaneous transactions can't both read the old
     * figure and overwrite each other.
     *
     * Must be called inside DB::transaction().
     */
    public static function locked(): self
    {
        $balance = static::query()->lockForUpdate()->first();

        if (! $balance) {
            static::current();
            $balance = static::query()->lockForUpdate()->firstOrFail();
        }

        return $balance;
    }

    /**
     * Apply signed changes to both balances.
     *
     * @param  array{gcash: float, cash: float}  $deltas
     */
    public function applyDeltas(array $deltas): void
    {
        $this->update([
            'gcash_balance' => round((float) $this->gcash_balance + $deltas['gcash'], 2),
            'cash_balance' => round((float) $this->cash_balance + $deltas['cash'], 2),
        ]);
    }

    /**
     * Whether both balances can absorb the given changes without going
     * negative. You cannot send GCash you don't have, or hand out cash that
     * isn't in the drawer.
     *
     * @param  array{gcash: float, cash: float}  $deltas
     */
    public function canAbsorb(array $deltas): bool
    {
        return round((float) $this->gcash_balance + $deltas['gcash'], 2) >= 0
            && round((float) $this->cash_balance + $deltas['cash'], 2) >= 0;
    }
}
