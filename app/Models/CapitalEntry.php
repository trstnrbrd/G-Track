<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the owner's puhunan / utang ledger.
 *
 *   capital  Dagdag puhunan — the capital grows (saved up, or reinvested tubo).
 *   borrow   Kumuha — money taken out of the GCash business (grocery, bills).
 *   repay    Nagbayad — money put back.
 *
 * `balance` says which of the two balances physically moved. NULL means
 * nothing moved: the money was already in the wallet (repaying out of
 * earnings), so only the ledger changes.
 *
 * Puhunan − utang is what should be sitting in the GCash business.
 */
class CapitalEntry extends Model
{
    public const KIND_BORROW = 'borrow';

    public const KIND_REPAY = 'repay';

    public const KIND_CAPITAL = 'capital';

    public const BALANCE_CASH = 'cash';

    public const BALANCE_GCASH = 'gcash';

    /** @var array<string, string> */
    public const PURPOSES = [
        'grocery' => 'Grocery',
        'kuryente' => 'Electricity',
        'tubig' => 'Water',
        'iba' => 'Other',
    ];

    protected $fillable = [
        'daily_session_id',
        'user_id',
        'kind',
        'amount',
        'balance',
        'purpose',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * How an entry moves the two balances.
     *
     * Taking money out lowers the balance it came from; repaying or adding
     * capital raises the one it was put into. With no balance, nothing moves.
     *
     * @return array{gcash: float, cash: float}
     */
    public static function deltasFor(string $kind, float $amount, ?string $balance): array
    {
        $signed = $kind === self::KIND_BORROW ? -$amount : $amount;

        return [
            'gcash' => $balance === self::BALANCE_GCASH ? round($signed, 2) : 0.0,
            'cash' => $balance === self::BALANCE_CASH ? round($signed, 2) : 0.0,
        ];
    }

    /** The owner's total puhunan. */
    public static function puhunan(): float
    {
        return round((float) static::query()->where('kind', self::KIND_CAPITAL)->sum('amount'), 2);
    }

    /** What is currently owed back to the GCash business. */
    public static function utang(): float
    {
        $totals = static::query()
            ->whereIn('kind', [self::KIND_BORROW, self::KIND_REPAY])
            ->selectRaw('kind, sum(amount) as total')
            ->groupBy('kind')
            ->pluck('total', 'kind');

        return round((float) ($totals[self::KIND_BORROW] ?? 0) - (float) ($totals[self::KIND_REPAY] ?? 0), 2);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(DailySession::class, 'daily_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
