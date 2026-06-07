<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySession extends Model
{
    protected $fillable = [
        'user_id',
        'opening_gcash_balance',
        'opening_cash_balance',
        'closing_gcash_balance',
        'closing_cash_balance',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'opening_gcash_balance' => 'decimal:2',
        'opening_cash_balance' => 'decimal:2',
        'closing_gcash_balance' => 'decimal:2',
        'closing_cash_balance' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * The currently active session, if any.
     */
    public static function active(): ?self
    {
        return static::query()->where('status', 'active')->latest('id')->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
