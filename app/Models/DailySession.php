<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySession extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'opening_gcash_balance',
        'opening_cash_balance',
        'closing_gcash_balance',
        'closing_cash_balance',
        'status',
        'auto_closed',
        'active_guard',
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
     * The currently active session, if any. Read-only callers (the dashboard)
     * should use this.
     */
    public static function active(): ?self
    {
        return static::query()->where('status', self::STATUS_ACTIVE)->latest('id')->first();
    }

    /**
     * The active session, locked for the rest of the surrounding database
     * transaction. Use this before writing anything that depends on the day
     * still being open, so the day can't be closed out from under you.
     *
     * Must be called inside DB::transaction().
     */
    public static function activeLocked(): ?self
    {
        return static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Close this session, releasing the unique active_guard so the next Start
     * Day can claim it. Always close through this method — setting status
     * alone would leave the guard held and block every future session.
     */
    public function close(float $closingGcash, float $closingCash, bool $auto = false): void
    {
        $this->update([
            'closing_gcash_balance' => $closingGcash,
            'closing_cash_balance' => $closingCash,
            'status' => self::STATUS_CLOSED,
            'auto_closed' => $auto,
            'active_guard' => null,
            'ended_at' => now(),
        ]);
    }

    /**
     * How long the day ran, as "5h 32m". For a session still open this counts
     * up from the start time; for a closed one it is start → end.
     *
     * Returns null only if started_at is somehow missing.
     */
    public function duration(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        // Carbon 3 returns a signed float here (e.g. 492.9 or -480.0), not an
        // int. Truncate to whole minutes: intdiv() and % need ints, and passing
        // them a float is deprecated.
        $minutes = (int) $this->started_at->diffInMinutes($this->ended_at ?? now());

        // A clock that runs backwards means the row is corrupt rather than the
        // day being short — say so plainly instead of printing "-480m". (This
        // relies on Carbon 3's result being signed.)
        if ($minutes < 0) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $hours > 0 ? $hours.'h '.$remainder.'m' : $remainder.'m';
    }

    /**
     * Net service charge earned during this session — the shop's takings for
     * the day, regardless of which balance each fee landed in.
     */
    public function earnings(): float
    {
        return (float) $this->transactions()->sum('service_charge');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
