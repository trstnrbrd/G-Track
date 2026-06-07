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
     * Get the single balances row, creating it if it doesn't exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'gcash_balance' => 0,
            'cash_balance' => 0,
        ]);
    }
}
