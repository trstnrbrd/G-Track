<?php

namespace App\Console\Commands;

use App\Models\Balance;
use App\Models\DailySession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseStaleSessions extends Command
{
    protected $signature = 'gtrack:close-stale-sessions
                            {--dry-run : Report what would be closed without changing anything}';

    protected $description = 'Close a daily session that was left open past the configured cutoff';

    /**
     * Close a day the operator forgot to end.
     *
     * If a session stays open overnight, the next day's transactions attach to
     * yesterday's session and every report silently blends two days together.
     * This closes it instead — but records `auto_closed`, because these closing
     * balances are what the app believed, not what anyone counted in the drawer.
     */
    public function handle(): int
    {
        $session = DailySession::active();

        if (! $session) {
            $this->info('No active session. Nothing to do.');

            return self::SUCCESS;
        }

        $cutoffHours = (int) config('gtrack.auto_close_after_hours');

        // Age is measured from the start of the day, not from the last
        // transaction — a quiet afternoon should not close the shop.
        $hoursOpen = $session->started_at->diffInHours(now());

        if ($hoursOpen < $cutoffHours) {
            $this->info(sprintf(
                'Session #%d has been open %dh, under the %dh cutoff. Leaving it alone.',
                $session->id, $hoursOpen, $cutoffHours,
            ));

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn(sprintf(
                'Would close session #%d (open %dh since %s).',
                $session->id, $hoursOpen, $session->started_at->format('M j, g:i A'),
            ));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($session, $hoursOpen) {
            // Re-read under a lock: the operator may have pressed End Day in the
            // seconds since we checked.
            $locked = DailySession::activeLocked();

            if (! $locked || $locked->id !== $session->id) {
                $this->info('Session was closed while we were working. Nothing to do.');

                return;
            }

            $balance = Balance::locked();

            $locked->close(
                (float) $balance->gcash_balance,
                (float) $balance->cash_balance,
                auto: true,
            );

            $resets = config('gtrack.end_day_resets');

            $balance->update([
                'gcash_balance' => $resets['gcash'] ? 0 : $balance->gcash_balance,
                'cash_balance' => $resets['cash'] ? 0 : $balance->cash_balance,
            ]);

            Log::warning('Auto-closed a forgotten GTrack session.', [
                'session_id' => $locked->id,
                'hours_open' => $hoursOpen,
                'closing_gcash' => $locked->closing_gcash_balance,
                'closing_cash' => $locked->closing_cash_balance,
            ]);

            $this->warn(sprintf('Auto-closed session #%d after %dh open.', $locked->id, $hoursOpen));
        });

        return self::SUCCESS;
    }
}
