<?php

namespace Tests\Unit;

use App\Models\DailySession;
use Tests\TestCase;

/**
 * DailySession::duration() — the "3h 20m" shown on the Balance page and in the
 * PDF. Built in memory; no database needed.
 */
class DailySessionDurationTest extends TestCase
{
    private function sessionLasting(int $seconds, bool $open = false): DailySession
    {
        $session = new DailySession;
        $session->started_at = now()->subSeconds($seconds);
        $session->ended_at = $open ? null : now();

        return $session;
    }

    public function test_hours_and_minutes(): void
    {
        $this->assertSame('3h 20m', $this->sessionLasting(200 * 60)->duration());
    }

    public function test_under_an_hour_shows_minutes_only(): void
    {
        $this->assertSame('45m', $this->sessionLasting(45 * 60)->duration());
    }

    /** Leftover seconds are dropped, not rounded up into the next minute. */
    public function test_partial_minutes_are_truncated(): void
    {
        $this->assertSame('2h 5m', $this->sessionLasting(125 * 60 + 50)->duration());
    }

    public function test_an_open_session_counts_up_to_now(): void
    {
        $this->assertSame('1h 30m', $this->sessionLasting(90 * 60, open: true)->duration());
    }

    /** A row whose end is before its start is corrupt — show nothing, not "-480m". */
    public function test_a_session_that_ends_before_it_starts_has_no_duration(): void
    {
        $session = new DailySession;
        $session->started_at = now();
        $session->ended_at = now()->subHours(8);

        $this->assertNull($session->duration());
    }

    /**
     * Carbon 3 returns minutes as a float. Passing that to intdiv() is
     * deprecated, and Laravel only logs deprecations quietly — which is how
     * this once slipped past the whole suite. Turn them into failures here.
     */
    public function test_it_raises_no_deprecation_warnings(): void
    {
        set_error_handler(
            fn (int $level, string $message) => throw new \ErrorException($message, 0, $level),
            E_DEPRECATED | E_USER_DEPRECATED,
        );

        try {
            $this->assertSame('8h 12m', $this->sessionLasting(492 * 60 + 54)->duration());
        } finally {
            restore_error_handler();
        }
    }
}
