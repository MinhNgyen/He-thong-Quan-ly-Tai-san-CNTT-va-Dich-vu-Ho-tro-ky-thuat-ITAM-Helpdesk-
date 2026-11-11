<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use App\Models\Escalation;
use App\Models\Holiday;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class SlaRepository
{
    public function resolutionMinutes(string $priority): ?int
    {
        $minutes = SlaPolicy::where('priority', $priority)->value('resolution_minutes');

        return $minutes === null ? null : (int) $minutes;
    }

    /** @return Collection<int, SlaPolicy> */
    public function policies(): Collection
    {
        return SlaPolicy::orderBy('resolution_minutes')->get();
    }

    /** @return list<string> ngày nghỉ dạng Y-m-d trong khoảng [from, to] */
    public function holidayDates(CarbonInterface $from, CarbonInterface $to): array
    {
        return Holiday::whereBetween('holiday_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->pluck('holiday_date')
            ->map(fn ($d) => $d instanceof CarbonInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10))
            ->all();
    }

    /** @return list<string> toàn bộ ngày nghỉ dạng Y-m-d (bảng nhỏ, nạp một lần mỗi request) */
    public function allHolidayDates(): array
    {
        return Holiday::query()->pluck('holiday_date')
            ->map(fn ($d) => $d instanceof CarbonInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10))
            ->all();
    }

    /** Phiếu đang chờ IT xử lý, đồng hồ SLA đang chạy, đã quá hạn và chưa leo thang. */
    public function overdueUnescalated(CarbonInterface $now): Collection
    {
        return Ticket::query()
            ->whereIn('status', [TicketStatus::Open->value, TicketStatus::Assigned->value, TicketStatus::InProgress->value])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', $now)
            ->whereNull('sla_escalated_at')
            ->get();
    }

    /** Đánh dấu leo thang theo kiểu nguyên tử: chỉ một tiến trình thắng khi chạy lệnh song song. */
    public function markEscalated(Ticket $ticket, CarbonInterface $at): bool
    {
        return Ticket::whereKey($ticket->id)
            ->whereNull('sla_escalated_at')
            ->update(['sla_escalated_at' => $at]) > 0;
    }

    public function createEscalation(array $attributes): Escalation
    {
        return Escalation::create($attributes);
    }
}
