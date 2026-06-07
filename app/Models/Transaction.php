<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'daily_session_id',
        'user_id',
        'type',
        'reference_number',
        'mobile_number',
        'amount',
        'service_charge',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
    ];

    /**
     * Service charge brackets (Philippine Peso):
     *   1 - 500    => 10
     *   501 - 1000 => 15
     *   1001 - 2000 => 20
     *   2001 - 5000 => 25
     *   5001+      => 30
     */
    public static function serviceChargeFor(float $amount): int
    {
        return match (true) {
            $amount <= 500  => 10,
            $amount <= 1000 => 15,
            $amount <= 2000 => 20,
            $amount <= 5000 => 25,
            default         => 30,
        };
    }

    public function isCashIn(): bool
    {
        return $this->type === 'cash_in';
    }

    public function isCashOut(): bool
    {
        return $this->type === 'cash_out';
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
