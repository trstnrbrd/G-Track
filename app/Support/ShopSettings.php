<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;

/**
 * The shop rules the owner can change from Settings.
 *
 * config/gtrack.php holds the defaults; a row in `settings` overrides one.
 * Anything stored that doesn't pass the same sanity checks the form applies is
 * ignored in favour of the default — a bad row must never be able to break
 * recording a transaction.
 *
 * Not cached on purpose: the lookups are one tiny indexed query, and a cache
 * would have to be invalidated across the request that saved a change.
 */
final class ShopSettings
{
    public const BRACKETS = 'service_charge_brackets';

    public const QUICK_AMOUNTS = 'quick_amounts';

    public const AUTO_CLOSE_HOURS = 'auto_close_after_hours';

    public const LAST_BACKUP = 'last_backup';

    /**
     * @return array<int, array{up_to: int|float|null, fee: int|float}>
     */
    public static function serviceChargeBrackets(): array
    {
        $stored = self::stored(self::BRACKETS);

        return is_array($stored) && self::validBrackets($stored)
            ? array_values($stored)
            : config('gtrack.service_charge_brackets');
    }

    /**
     * @return array<int, int|float>
     */
    public static function quickAmounts(): array
    {
        $stored = self::stored(self::QUICK_AMOUNTS);

        return is_array($stored) && self::validQuickAmounts($stored)
            ? array_values($stored)
            : config('gtrack.quick_amounts');
    }

    public static function autoCloseAfterHours(): int
    {
        $stored = self::stored(self::AUTO_CLOSE_HOURS);

        return is_numeric($stored) && (int) $stored >= 1 && (int) $stored <= 48
            ? (int) $stored
            : (int) config('gtrack.auto_close_after_hours');
    }

    /** Whether the owner has changed this one from the shipped default. */
    public static function isCustom(string $key): bool
    {
        return self::row($key) !== null;
    }

    /** Who last changed it, and when — for the line under each setting. */
    public static function lastChange(string $key): ?Setting
    {
        return self::row($key)?->loadMissing('updatedBy');
    }

    // ---- the same checks the form applies ---------------------------------------

    /**
     * A non-empty list, every fee >= 0, every "up_to" a positive number that
     * strictly rises, and the last one "up_to" => null (the catch-all).
     *
     * @param  array<int, mixed>  $brackets
     */
    public static function validBrackets(array $brackets): bool
    {
        $brackets = array_values($brackets);
        $count = count($brackets);
        $previous = 0;

        if ($count === 0 || $count > 12) {
            return false;
        }

        foreach ($brackets as $index => $bracket) {
            if (! is_array($bracket) || ! array_key_exists('fee', $bracket) || ! is_numeric($bracket['fee']) || $bracket['fee'] < 0) {
                return false;
            }

            $isLast = $index === $count - 1;
            $upTo = $bracket['up_to'] ?? null;

            if ($isLast) {
                if ($upTo !== null) {
                    return false;
                }

                continue;
            }

            if (! is_numeric($upTo) || $upTo <= $previous) {
                return false;
            }

            $previous = $upTo;
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $amounts
     */
    public static function validQuickAmounts(array $amounts): bool
    {
        $amounts = array_values($amounts);

        if ($amounts === [] || count($amounts) > 8 || count(array_unique($amounts)) !== count($amounts)) {
            return false;
        }

        foreach ($amounts as $amount) {
            if (! is_numeric($amount) || $amount < 1 || $amount > 1000000) {
                return false;
            }
        }

        return true;
    }

    // ---- storage ------------------------------------------------------------------

    private static function row(string $key): ?Setting
    {
        try {
            return Setting::query()->where('key', $key)->first();
        } catch (QueryException) {
            // The table isn't there yet (migrations not run): use the defaults.
            return null;
        }
    }

    private static function stored(string $key): mixed
    {
        return self::row($key)?->value;
    }
}
