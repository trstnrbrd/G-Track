<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mark sessions the system closed on the operator's behalf.
     *
     * A day left open overnight silently swallows tomorrow's transactions, so
     * the scheduler closes it. But an auto-closed day was never counted by a
     * human — its closing balances are whatever the app believed at the time,
     * not what was actually in the drawer. That distinction has to survive into
     * the reports, otherwise a guess looks exactly like a verified count.
     */
    public function up(): void
    {
        Schema::table('daily_sessions', function (Blueprint $table) {
            $table->boolean('auto_closed')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('daily_sessions', function (Blueprint $table) {
            $table->dropColumn('auto_closed');
        });
    }
};
