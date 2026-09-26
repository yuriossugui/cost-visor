<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;
use App\Services\BusinessDayService;
use App\Models\Holiday;
use Carbon\Carbon;

class BusinessDayServiceTest extends TestCase
{
    protected BusinessDayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BusinessDayService();
    }

    public function test_saturday_is_business_day(): void
    {
        $saturday = Carbon::create(2026, 1, 3); // 2026-01-03 is a Saturday
        $this->assertTrue($this->service->isBusinessDay($saturday));
    }

    public function test_sunday_is_not_business_day(): void
    {
        $sunday = Carbon::create(2026, 1, 4); // 2026-01-04 is a Sunday
        $this->assertFalse($this->service->isBusinessDay($sunday));
    }

    public function test_weekday_is_business_day(): void
    {
        $monday = Carbon::create(2026, 1, 5); // 2026-01-05 is a Monday
        $this->assertTrue($this->service->isBusinessDay($monday));
    }

    public function test_holiday_is_not_business_day(): void
    {
        Holiday::create([
            'date' => '2026-01-05',
            'name' => 'Test Holiday',
        ]);

        $holiday = Carbon::create(2026, 1, 5); // Monday but holiday
        $this->assertFalse($this->service->isBusinessDay($holiday));
    }

    public function test_holiday_on_sunday_still_not_business_day(): void
    {
        Holiday::create([
            'date' => '2026-01-04',
            'name' => 'Sunday Holiday',
        ]);

        $sunday = Carbon::create(2026, 1, 4);
        $this->assertFalse($this->service->isBusinessDay($sunday));
    }

    public function test_get_next_business_day_from_friday(): void
    {
        $friday = Carbon::create(2026, 1, 2); // Friday
        $next = $this->service->getNextBusinessDay($friday);

        // Saturday is next business day
        $this->assertEquals(Carbon::create(2026, 1, 3), $next);
    }

    public function test_get_next_business_day_from_saturday(): void
    {
        $saturday = Carbon::create(2026, 1, 3); // Saturday
        $next = $this->service->getNextBusinessDay($saturday);

        // Monday (Sunday is not business day)
        $this->assertEquals(Carbon::create(2026, 1, 5), $next);
    }

    public function test_get_next_business_day_skips_holiday(): void
    {
        Holiday::create([
            'date' => '2026-01-05',
            'name' => 'Test Holiday',
        ]);

        $friday = Carbon::create(2026, 1, 2); // Friday
        $next = $this->service->getNextBusinessDay($friday);

        // Saturday is next (holiday on Monday)
        $this->assertEquals(Carbon::create(2026, 1, 3), $next);
    }

    public function test_get_nth_business_day(): void
    {
        // Jan 1 2026 is Thursday
        $start = Carbon::create(2026, 1, 1);

        // 1st business day = Friday Jan 2
        $first = $this->service->getNthBusinessDay($start, 1);
        $this->assertEquals(Carbon::create(2026, 1, 2), $first);

        // 2nd business day = Saturday Jan 3
        $second = $this->service->getNthBusinessDay($start, 2);
        $this->assertEquals(Carbon::create(2026, 1, 3), $second);

        // 3rd business day = Monday Jan 5 (skips Sunday)
        $third = $this->service->getNthBusinessDay($start, 3);
        $this->assertEquals(Carbon::create(2026, 1, 5), $third);
    }

    public function test_get_business_days_in_range(): void
    {
        $start = Carbon::create(2026, 1, 1); // Thursday
        $end = Carbon::create(2026, 1, 7);   // Wednesday

        $days = $this->service->getBusinessDaysInRange($start, $end);

        // Jan 1 (Thu), 2 (Fri), 3 (Sat), 5 (Mon), 6 (Tue), 7 (Wed) = 6 days
        // Jan 4 (Sun) is not a business day
        $this->assertCount(6, $days);
    }

    public function test_count_business_days_in_range(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $end = Carbon::create(2026, 1, 7);

        $count = $this->service->countBusinessDaysInRange($start, $end);
        $this->assertEquals(6, $count);
    }

    public function test_add_business_days(): void
    {
        $start = Carbon::create(2026, 1, 2); // Friday

        $result = $this->service->addBusinessDays($start, 1);
        $this->assertEquals(Carbon::create(2026, 1, 3), $result); // Saturday

        $result = $this->service->addBusinessDays($start, 2);
        $this->assertEquals(Carbon::create(2026, 1, 5), $result); // Monday (skips Sunday)
    }

    public function test_business_day_occurrence_dates_daily(): void
    {
        $start = Carbon::create(2026, 1, 2); // Friday
        $end = Carbon::create(2026, 1, 7);   // Wednesday

        $dates = $this->service->getBusinessDayOccurrenceDates($start, $end, 'daily');

        // Fri Jan 2, Sat Jan 3, Mon Jan 5, Tue Jan 6, Wed Jan 7
        $this->assertCount(5, $dates);
        $this->assertEquals('2026-01-02', $dates[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-03', $dates[1]->format('Y-m-d'));
        $this->assertEquals('2026-01-05', $dates[2]->format('Y-m-d'));
    }

    public function test_business_day_occurrence_dates_weekly(): void
    {
        $start = Carbon::create(2026, 1, 2); // Friday
        $end = Carbon::create(2026, 2, 2);

        $dates = $this->service->getBusinessDayOccurrenceDates($start, $end, 'weekly');

        // Jan 2, Jan 9, Jan 16, Jan 23, Jan 30
        $this->assertCount(5, $dates);
    }

    public function test_business_day_occurrence_skips_holidays(): void
    {
        Holiday::create([
            'date' => '2026-01-05', // Monday
            'name' => 'Holiday',
        ]);

        $start = Carbon::create(2026, 1, 2); // Friday
        $end = Carbon::create(2026, 1, 7);

        $dates = $this->service->getBusinessDayOccurrenceDates($start, $end, 'daily');

        // Should skip Monday Jan 5 (holiday)
        $dateStrings = array_map(fn($d) => $d->format('Y-m-d'), $dates);
        $this->assertNotContains('2026-01-05', $dateStrings);
    }
}