<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;

class ComplaintDeadlineService
{
    public function addBusinessDays(CarbonInterface $date, int $businessDays): CarbonImmutable
    {
        $result = CarbonImmutable::instance($date);
        $daysAdded = 0;

        while ($daysAdded < $businessDays) {
            $result = $result->addDay();

            if ($this->isBusinessDay($result)) {
                $daysAdded++;
            }
        }

        return $result;
    }

    private function isBusinessDay(CarbonInterface $date): bool
    {
        return $date->isWeekday() && ! $this->isPeruvianHoliday($date);
    }

    private function isPeruvianHoliday(CarbonInterface $date): bool
    {
        $year = $date->year;
        $fixedHolidays = [
            "$year-01-01",
            "$year-05-01",
            "$year-06-07",
            "$year-06-29",
            "$year-07-23",
            "$year-07-28",
            "$year-07-29",
            "$year-08-06",
            "$year-08-30",
            "$year-11-01",
            "$year-12-08",
            "$year-12-09",
            "$year-12-25",
        ];

        if (in_array($date->toDateString(), $fixedHolidays, true)) {
            return true;
        }

        $easterSunday = CarbonImmutable::createFromTimestampUTC(easter_date($year));

        return $date->isSameDay($easterSunday->subDays(3))
            || $date->isSameDay($easterSunday->subDays(2));
    }
}
