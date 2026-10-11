<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A store item the customer paid for with GCash.
 *
 * The money lands in the GCash wallet, but it belongs to the tindahan. So the
 * same amount of physical cash is taken out of the GCash drawer and put in the
 * tindahan's till. The tindahan's own balance is out of scope — we only record
 * what was moved to it.
 */
class StoreSale extends Model
{
    protected $fillable = [
        'daily_session_id',
        'user_id',
        'amount',
        'reference_number',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * How settling the sale moves the GCash business's two balances.
     *
     *   Item ₱500 paid via GCash → gcash +500, cash −500
     *
     * The same movement as a cash out with no fee: the GCash side nets zero,
     * which keeps the Balance page's "net change = fees earned" check true.
     *
     * @return array{gcash: float, cash: float}
     */
    public static function deltasFor(float $amount): array
    {
        return [
            'gcash' => round($amount, 2),
            'cash' => round(-$amount, 2),
        ];
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
