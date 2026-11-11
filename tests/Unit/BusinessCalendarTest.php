<?php

namespace Tests\Unit;

use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/** Kiểm thử thuật toán giờ làm việc của đồng hồ SLA (08:00-17:00, thứ Hai-thứ Sáu, giờ Việt Nam). */
class BusinessCalendarTest extends TestCase
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    private function calendar(array $holidays = []): BusinessCalendar
    {
        return new BusinessCalendar(self::TZ, [1, 2, 3, 4, 5], 480, 1020, $holidays);
    }

    private function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, self::TZ);
    }

    private function local(CarbonImmutable $moment): string
    {
        return $moment->setTimezone(self::TZ)->format('Y-m-d H:i');
    }

    public function test_friday_17h_plus_4_business_hours_is_monday_noon(): void
    {
        // 2026-11-06 là thứ Sáu. Đúng tiêu chí nghiệm thu đề tài 24.
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-06 17:00'), 240);

        $this->assertSame('2026-11-09 12:00', $this->local($due));
    }

    public function test_within_the_same_working_day(): void
    {
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-09 09:30'), 90);

        $this->assertSame('2026-11-09 11:00', $this->local($due));
    }

    public function test_ending_exactly_at_close_of_day_stays_on_that_day(): void
    {
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-09 13:00'), 240);

        $this->assertSame('2026-11-09 17:00', $this->local($due));
    }

    public function test_spills_over_to_next_day(): void
    {
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-09 16:00'), 120);

        $this->assertSame('2026-11-10 09:00', $this->local($due));
    }

    public function test_created_before_opening_hours_starts_at_opening(): void
    {
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-09 06:00'), 60);

        $this->assertSame('2026-11-09 09:00', $this->local($due));
    }

    public function test_created_on_weekend_starts_monday(): void
    {
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-08 10:00'), 60); // Chủ nhật

        $this->assertSame('2026-11-09 09:00', $this->local($due));
    }

    public function test_holiday_is_skipped(): void
    {
        // Thứ Hai 2026-11-09 là ngày nghỉ => bắt đầu tính từ thứ Ba.
        $due = $this->calendar(['2026-11-09'])->addBusinessMinutes($this->at('2026-11-06 17:00'), 240);

        $this->assertSame('2026-11-10 12:00', $this->local($due));
    }

    public function test_multi_day_sla_of_24_business_hours_skips_the_weekend(): void
    {
        // Mỗi ngày làm việc 9 giờ: thứ Sáu 9h + thứ Hai 9h = 18h, còn 6h => thứ Ba 08:00 + 6h = 14:00 (bỏ qua thứ Bảy, Chủ nhật).
        $due = $this->calendar()->addBusinessMinutes($this->at('2026-11-06 08:00'), 1440);

        $this->assertSame('2026-11-10 14:00', $this->local($due));
    }

    public function test_zero_minutes_returns_same_moment(): void
    {
        $from = $this->at('2026-11-09 10:00');

        $this->assertSame($this->local($from), $this->local($this->calendar()->addBusinessMinutes($from, 0)));
    }

    public function test_business_minutes_between_ignores_nights_and_weekend(): void
    {
        $minutes = $this->calendar()->businessMinutesBetween($this->at('2026-11-06 16:00'), $this->at('2026-11-09 10:00'));

        $this->assertSame(60 + 120, $minutes); // thứ Sáu 16-17h + thứ Hai 08-10h
    }

    public function test_business_minutes_between_is_zero_when_reversed(): void
    {
        $this->assertSame(0, $this->calendar()->businessMinutesBetween($this->at('2026-11-09 10:00'), $this->at('2026-11-09 09:00')));
    }

    public function test_add_and_between_are_consistent(): void
    {
        $calendar = $this->calendar(['2026-11-10']);
        $from = $this->at('2026-11-06 15:20');

        foreach ([1, 59, 540, 541, 1440, 3000] as $minutes) {
            $due = $calendar->addBusinessMinutes($from, $minutes);

            $this->assertSame($minutes, $calendar->businessMinutesBetween($from, $due), "Lệch với {$minutes} phút");
        }
    }

    public function test_is_working_day(): void
    {
        $calendar = $this->calendar(['2026-11-11']);

        $this->assertTrue($calendar->isWorkingDay($this->at('2026-11-09 12:00')));
        $this->assertFalse($calendar->isWorkingDay($this->at('2026-11-07 12:00'))); // thứ Bảy
        $this->assertFalse($calendar->isWorkingDay($this->at('2026-11-11 12:00'))); // ngày nghỉ
    }
}
