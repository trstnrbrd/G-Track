<?php

namespace Tests\Unit;

use App\Support\DayLabel;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DayLabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A Monday afternoon in 2026.
        Carbon::setTestNow('2026-10-12 15:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_today_and_yesterday_are_named(): void
    {
        $this->assertSame('Today · Mon, Oct 12', DayLabel::for(Carbon::parse('2026-10-12 08:00:00')));
        $this->assertSame('Yesterday · Sun, Oct 11', DayLabel::for(Carbon::parse('2026-10-11 23:59:00')));
    }

    public function test_other_days_are_just_the_date(): void
    {
        $this->assertSame('Fri, Oct 9', DayLabel::for(Carbon::parse('2026-10-09 10:00:00')));
    }

    public function test_the_year_appears_only_when_it_is_not_this_year(): void
    {
        $this->assertSame('Wed, Dec 31, 2025', DayLabel::for(Carbon::parse('2025-12-31 20:00:00')));
        $this->assertSame('Thu, Jan 1', DayLabel::for(Carbon::parse('2026-01-01 00:10:00')));
    }

    public function test_a_late_night_entry_belongs_to_its_own_day(): void
    {
        // 11:58 PM Sunday is still Sunday, not Monday.
        $this->assertStringStartsWith('Yesterday', DayLabel::for(Carbon::parse('2026-10-11 23:58:00')));
        $this->assertStringStartsWith('Today', DayLabel::for(Carbon::parse('2026-10-12 00:02:00')));
    }
}
