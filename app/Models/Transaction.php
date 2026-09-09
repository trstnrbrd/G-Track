<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';

    public const PAID_IN_CASH = 'cash';
    public const PAID_IN_GCASH = 'gcash';

    protected $fillable = [
        'daily_session_id',
        'user_id',
        'type',
        'reference_number',
        'mobile_number',
        'amount',
        'service_charge',
        'charge_paid_in',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
    ];

    /**
     * The service charge for a given amount, from config/gtrack.php.
     *
     * This is the only place the rates are computed. Never accept a charge
     * sent by the browser — the modal's preview is a convenience, not a source
     * of truth.
     */
    public static function serviceChargeFor(float $amount): float
    {
        foreach (config('gtrack.service_charge_brackets') as $bracket) {
            if ($bracket['up_to'] === null || $amount <= $bracket['up_to']) {
                return (float) $bracket['fee'];
            }
        }

        // Unreachable with a well-formed config (the last bracket is the
        // catch-all), but a missing charge is safer than a silent zero.
        throw new \RuntimeException('No service charge bracket matched amount '.$amount);
    }

    /**
     * How this transaction moves the two balances.
     *
     * The amount always leaves one balance and enters the other; the service
     * charge is then added to whichever balance the customer settled it in.
     * That single rule covers all four real-world cases:
     *
     *   Cash In ₱500, fee ₱15 paid in cash   → cash +515, gcash -500
     *   Cash In ₱500, fee ₱15 deducted       → cash +500, gcash -485
     *   Cash Out ₱500, fee ₱15 paid in cash  → gcash +500, cash -485
     *   Cash Out ₱500, fee ₱15 sent in GCash → gcash +515, cash -500
     *
     * In every case the shop nets the ₱15 charge.
     *
     * @return array{gcash: float, cash: float}
     */
    public static function deltasFor(string $type, float $amount, float $serviceCharge, string $chargePaidIn): array
    {
        // Cash In sends GCash out of the wallet; Cash Out receives it.
        $gcashSign = $type === self::TYPE_CASH_IN ? -1 : 1;

        $deltas = [
            'gcash' => $gcashSign * $amount,
            'cash' => -$gcashSign * $amount,
        ];

        $deltas[$chargePaidIn === self::PAID_IN_GCASH ? 'gcash' : 'cash'] += $serviceCharge;

        return [
            'gcash' => round($deltas['gcash'], 2),
            'cash' => round($deltas['cash'], 2),
        ];
    }

    /**
     * How this saved transaction moves the two balances.
     *
     * @return array{gcash: float, cash: float}
     */
    public function balanceDeltas(): array
    {
        return static::deltasFor(
            $this->type,
            (float) $this->amount,
            (float) $this->service_charge,
            $this->charge_paid_in,
        );
    }

    public function isCashIn(): bool
    {
        return $this->type === self::TYPE_CASH_IN;
    }

    public function isCashOut(): bool
    {
        return $this->type === self::TYPE_CASH_OUT;
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
