<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce "at most one active session" in the database itself.
     *
     * Application checks and row locks are not enough on their own: when no
     * session exists yet there is no row to lock, so two simultaneous Start Day
     * requests could both find nothing and both insert.
     *
     * The trick is a nullable unique column. Both MySQL and SQLite allow any
     * number of NULLs in a unique index, so closed sessions (NULL) never
     * collide, while an active session holds the single value 1 and a second
     * insert fails outright.
     */
    public function up(): void
    {
        Schema::table('daily_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_guard')->nullable()->after('status');
            $table->unique('active_guard');
        });

        // Backfill: mark whichever session is currently open.
        $activeId = DB::table('daily_sessions')
            ->where('status', 'active')
            ->orderByDesc('id')
            ->value('id');

        if ($activeId) {
            // Any older rows still flagged active are stale — close them, since
            // only one can hold the guard.
            DB::table('daily_sessions')
                ->where('status', 'active')
                ->where('id', '!=', $activeId)
                ->update(['status' => 'closed', 'ended_at' => now()]);

            DB::table('daily_sessions')->where('id', $activeId)->update(['active_guard' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('daily_sessions', function (Blueprint $table) {
            $table->dropUnique(['active_guard']);
            $table->dropColumn('active_guard');
        });
    }
};
