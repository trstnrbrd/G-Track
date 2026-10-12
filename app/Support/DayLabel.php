<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * The label on the day dividers in the lists: "Today · Mon, Oct 12",
 * "Yesterday · Sun, Oct 11", then "Fri, Oct 9" (with the year once it isn't
 * this year). Dates are already in the app's timezone (Asia/Manila).
 */
final class DayLabel
{
    public static function for(CarbonInterface $date): string
    {
        $text = $date->format($date->isSameYear(now()) ? 'D, M j' : 'D, M j, Y');

        if ($date->isToday()) {
            return 'Today · '.$text;
        }

        if ($date->isYesterday()) {
            return 'Yesterday · '.$text;
        }

        return $text;
    }
}
