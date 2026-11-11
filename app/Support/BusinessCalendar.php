<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Lịch giờ làm việc dùng cho đồng hồ SLA. Lớp thuần (không truy cập CSDL) nên kiểm thử được độc lập.
 *
 * Quy ước: ngày làm việc là các thứ trong $workDays (ISO 1-7) trừ ngày nghỉ trong $holidays;
 * mỗi ngày làm việc có khoảng [$startMinute, $endMinute) tính theo múi giờ $timezone.
 */
class BusinessCalendar
{
    /** @var array<string, true> tập ngày nghỉ dạng Y-m-d để tra cứu O(1) */
    private array $holidays = [];

    /**
     * @param list<int> $workDays
     * @param list<string> $holidays danh sách ngày nghỉ dạng Y-m-d
     */
    public function __construct(
        private readonly string $timezone = 'Asia/Ho_Chi_Minh',
        private readonly array $workDays = [1, 2, 3, 4, 5],
        private readonly int $startMinute = 480,
        private readonly int $endMinute = 1020,
        array $holidays = [],
        private readonly int $maxScanDays = 3660,
    ) {
        foreach ($holidays as $date) {
            $this->holidays[$date] = true;
        }
    }

    public function isWorkingDay(CarbonInterface $moment): bool
    {
        $local = $this->local($moment);

        return in_array($local->dayOfWeekIso, $this->workDays, true)
            && ! isset($this->holidays[$local->format('Y-m-d')]);
    }

    /**
     * Cộng $minutes phút làm việc vào $from, trả về thời điểm đến hạn (giờ UTC của ứng dụng).
     * Khoảng nằm ngoài giờ làm việc không được tính: tạo phiếu 17:00 thứ Sáu + 4 giờ => 12:00 thứ Hai.
     */
    public function addBusinessMinutes(CarbonInterface $from, int $minutes): CarbonImmutable
    {
        $cursor = $this->local($from);
        $remaining = max(0, $minutes);

        if ($remaining === 0) {
            return $this->toAppTimezone($cursor);
        }

        for ($i = 0; $i <= $this->maxScanDays; $i++) {
            if (! $this->isWorkingDay($cursor)) {
                $cursor = $this->nextDayStart($cursor);
                continue;
            }

            $minuteOfDay = $cursor->hour * 60 + $cursor->minute;

            if ($minuteOfDay < $this->startMinute) {
                $cursor = $cursor->setTime(intdiv($this->startMinute, 60), $this->startMinute % 60);
                $minuteOfDay = $this->startMinute;
            }

            if ($minuteOfDay >= $this->endMinute) {
                $cursor = $this->nextDayStart($cursor);
                continue;
            }

            $available = $this->endMinute - $minuteOfDay;

            if ($remaining <= $available) {
                return $this->toAppTimezone($cursor->addMinutes($remaining));
            }

            $remaining -= $available;
            $cursor = $this->nextDayStart($cursor);
        }

        throw new RuntimeException('Không tìm được thời điểm đến hạn trong phạm vi quét cho phép.');
    }

    /** Số phút làm việc nằm giữa hai thời điểm (0 nếu $to <= $from). */
    public function businessMinutesBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $start = $this->local($from);
        $end = $this->local($to);

        if ($end <= $start) {
            return 0;
        }

        $total = 0;
        $day = $start->startOfDay();

        for ($i = 0; $i <= $this->maxScanDays && $day <= $end; $i++) {
            if ($this->isWorkingDay($day)) {
                $open = $day->setTime(intdiv($this->startMinute, 60), $this->startMinute % 60);
                $close = $day->setTime(intdiv($this->endMinute, 60), $this->endMinute % 60);

                $from1 = $start > $open ? $start : $open;
                $to1 = $end < $close ? $end : $close;

                if ($to1 > $from1) {
                    $total += (int) floor(($to1->getTimestamp() - $from1->getTimestamp()) / 60);
                }
            }

            $day = $day->addDay()->startOfDay();
        }

        return $total;
    }

    private function local(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone($this->timezone);
    }

    private function nextDayStart(CarbonImmutable $moment): CarbonImmutable
    {
        return $moment->addDay()->setTime(intdiv($this->startMinute, 60), $this->startMinute % 60);
    }

    private function toAppTimezone(CarbonImmutable $moment): CarbonImmutable
    {
        return $moment->setTimezone(config('app.timezone', 'UTC'));
    }
}
