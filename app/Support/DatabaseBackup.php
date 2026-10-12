<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A plain-SQL backup of the shop's own data: one INSERT per batch of rows.
 *
 * Done in PHP rather than with mysqldump so it works the same on any PC
 * (no path to a binary, no shell) and can be streamed straight to the browser.
 *
 * What is NOT in it, on purpose: sessions, cache, jobs and password-reset
 * tokens. They are temporary, and restoring them would only sign people in or
 * out at random.
 *
 * It does contain the users table (with password hashes), so it must be
 * treated as private — it is only handed to the owner.
 *
 * To restore: migrate an empty database (php artisan migrate:fresh, without
 * --seed), then import this file (phpMyAdmin → Import, or the mysql client).
 */
class DatabaseBackup
{
    /** Order matters: parents before the tables that point at them. */
    private const TABLES = [
        'users',
        'balances',
        'daily_sessions',
        'transactions',
        'store_sales',
        'capital_entries',
        'balance_adjustments',
        'settings',
    ];

    /**
     * @return array<int, string>
     */
    public static function tables(): array
    {
        return array_values(array_filter(self::TABLES, fn (string $table) => Schema::hasTable($table)));
    }

    public static function filename(?\DateTimeInterface $at = null): string
    {
        return 'gtrack-backup-'.($at ?? now())->format('Y-m-d-His').'.sql';
    }

    /**
     * Write the backup to $write, a chunk at a time. Returns the row count.
     *
     * @param  callable(string): void  $write
     */
    public static function stream(callable $write): int
    {
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();
        $total = 0;

        $write("-- GTrack backup\n");
        $write('-- Created: '.now()->format('Y-m-d H:i:s').' ('.config('app.timezone').")\n");
        $write('-- Tables: '.implode(', ', self::tables())."\n");
        $write("-- Restore: migrate an empty database (no --seed), then import this file.\n\n");

        // Rows are inserted parents-first, but foreign keys are relaxed anyway
        // so a restore can't trip over circular or nullable references.
        $write($driver === 'sqlite' ? "PRAGMA foreign_keys = OFF;\n\n" : "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        foreach (self::tables() as $table) {
            $count = 0;
            $write("-- {$table}\n");

            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $pdo, $write, &$count) {
                $columns = array_keys((array) $rows->first());
                $list = '`'.implode('`, `', $columns).'`';

                $values = $rows->map(function ($row) use ($pdo) {
                    return '('.implode(', ', array_map(
                        fn ($value) => self::literal($pdo, $value),
                        array_values((array) $row),
                    )).')';
                })->implode(",\n  ");

                $write("INSERT INTO `{$table}` ({$list}) VALUES\n  {$values};\n");
                $count += $rows->count();
            });

            $write("-- {$count} rows\n\n");
            $total += $count;
        }

        $write($driver === 'sqlite' ? "PRAGMA foreign_keys = ON;\n" : "SET FOREIGN_KEY_CHECKS = 1;\n");

        return $total;
    }

    /** The whole backup as one string (small shops; also handy in tests). */
    public static function toString(): string
    {
        $sql = '';

        self::stream(function (string $chunk) use (&$sql) {
            $sql .= $chunk;
        });

        return $sql;
    }

    private static function literal(\PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value) => (string) $value,
            // Quoted rather than bare: a decimal like 0.10 must keep its text.
            default => $pdo->quote((string) $value),
        };
    }
}
