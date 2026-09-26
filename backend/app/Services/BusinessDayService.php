<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BusinessDayService
{
    /**
     * Check if a given date is a business day.
     * Business day = not Sunday and not a holiday
     * Saturday IS a business day
     */
    public function isBusinessDay(Carbon $date): bool
    {
        // Sunday is not a business day
        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }

        // Check if it's a holiday
        if ($this->isHoliday($date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a given date is a holiday
     */
    public function isHoliday(Carbon $date): bool
    {
        return Holiday::whereDate('date', $date->format('Y-m-d'))->exists();
    }

    /**
     * Get the next business day after the given date
     */
    public function getNextBusinessDay(Carbon $date): Carbon
    {
        $next = $date->copy()->addDay();
        while (!$this->isBusinessDay($next)) {
            $next->addDay();
        }
        return $next;
    }

    /**
     * Get the N-th business day from a starting date
     */
    public function getNthBusinessDay(Carbon $startDate, int $n): Carbon
    {
        if ($n <= 0) {
            return $this->getFirstBusinessDayOnOrAfter($startDate);
        }

        $current = $startDate->copy();
        $count = 0;

        while ($count < $n) {
            $current->addDay();
            if ($this->isBusinessDay($current)) {
                $count++;
            }
        }

        return $current;
    }

    /**
     * Get the first business day on or after the given date
     */
    public function getFirstBusinessDayOnOrAfter(Carbon $date): Carbon
    {
        $current = $date->copy();
        while (!$this->isBusinessDay($current)) {
            $current->addDay();
        }
        return $current;
    }

    /**
     * Get all business days in a date range
     */
    public function getBusinessDaysInRange(Carbon $startDate, Carbon $endDate): array
    {
        $businessDays = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            if ($this->isBusinessDay($date)) {
                $businessDays[] = $date->copy();
            }
        }

        return $businessDays;
    }

    /**
     * Count business days in a date range
     */
    public function countBusinessDaysInRange(Carbon $startDate, Carbon $endDate): int
    {
        return count($this->getBusinessDaysInRange($startDate, $endDate));
    }

    /**
     * Add business days to a date
     */
    public function addBusinessDays(Carbon $date, int $days): Carbon
    {
        return $this->getNthBusinessDay($date, $days);
    }

    /**
     * Get occurrence dates for a recurrence, adjusted for business days
     * If the calculated date falls on a non-business day, move to next business day
     */
    public function getBusinessDayOccurrenceDates(Carbon $startDate, Carbon $endDate, string $frequency, int $nth = 1): array
    {
        $dates = [];
        $current = $startDate->copy();

        // Find the first business day on or after start date
        if (!$this->isBusinessDay($current)) {
            $current = $this->getNextBusinessDay($current);
        }

        while ($current->lte($endDate)) {
            // For monthly/yearly recurrences, we might want the N-th business day of the month
            if (in_array($frequency, ['monthly', 'yearly']) && $nth > 1) {
                $dates[] = $this->getNthBusinessDay($current->copy()->startOfMonth(), $nth - 1);
            } else {
                $dates[] = $current->copy();
            }

            // Move to next occurrence based on frequency
            switch ($frequency) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                case 'yearly':
                    $current->addYear();
                    break;
            }

            // Adjust to next business day if needed
            if (!$this->isBusinessDay($current)) {
                $current = $this->getNextBusinessDay($current);
            }
        }

        return $dates;
    }
}