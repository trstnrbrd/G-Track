<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stop MySQL from silently rewriting started_at on every update.
     *
     * MySQL/MariaDB has a legacy behaviour: the FIRST `TIMESTAMP` column in a
     * table that is NOT NULL and has no explicit default silently becomes
     *
     *     DEFAULT current_timestamp() ON UPDATE current_timestamp()
     *
     * `started_at` was exactly that. So every time the row was updated — most
     * importantly on End Day — MySQL overwrote the start time with the MySQL
     * server's clock. Worse, MySQL's clock runs in local time (UTC+8) while
     * Laravel writes UTC, so a closed session ended up with a start time eight
     * hours AHEAD of its end time.
     *
     * `DATETIME` has no such auto-update behaviour, which is why Laravel's own
     * `timestamps()` columns (nullable, so exempt) were never affected. Switching
     * these two columns to DATETIME fixes it permanently.
     *
     * SQLite has no such behaviour, so this is a no-op there and the test suite
     * is unaffected.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE daily_sessions MODIFY started_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE daily_sessions MODIFY ended_at DATETIME NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE daily_sessions MODIFY started_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE daily_sessions MODIFY ended_at TIMESTAMP NULL DEFAULT NULL');
    }
};
