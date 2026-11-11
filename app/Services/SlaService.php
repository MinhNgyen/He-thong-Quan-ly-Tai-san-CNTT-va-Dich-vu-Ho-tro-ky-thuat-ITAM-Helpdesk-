<?php

namespace App\Services;

use App\Models\Ticket;
use App\Repositories\SlaRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Support\BusinessCalendar;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Đồng hồ cam kết chất lượng dịch vụ (SLA) tính theo GIỜ LÀM VIỆC:
 * - hạn xử lý = thời điểm tạo + số phút làm việc theo mức ưu tiên (bỏ ngoài giờ, cuối tuần, ngày nghỉ);
 * - đồng hồ tạm dừng khi phiếu chờ người dùng phản hồi và chạy tiếp với số phút còn lại khi có phản hồi;
 * - phiếu quá hạn được leo thang đúng một lần.
 *
 * start/pause/resume chỉ thay đổi thuộc tính trên model; nơi gọi chịu trách nhiệm lưu.
 */
class SlaService
{
    /** @var list<string>|null ngày nghỉ nạp một lần để danh sách phiếu không phát sinh truy vấn lặp (N+1) */
    private ?array $holidays = null;

    public function __construct(
        private readonly SlaRepository $sla,
        private readonly TicketRepository $tickets,
        private readonly UserRepository $users,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {
    }

    public function minutesFor(string $priority): int
    {
        return $this->sla->resolutionMinutes($priority)
            ?? (int) config("sla.default_minutes.{$priority}", 480);
    }

    /** @return Collection<int, \App\Models\SlaPolicy> */
    public function policies(): Collection
    {
        return $this->sla->policies();
    }

    public function start(Ticket $ticket): void
    {
        $from = $ticket->created_at ?? now();

        $ticket->sla_due_at = $this->calendar($from)->addBusinessMinutes($from, $this->minutesFor((string) $ticket->priority));
        $ticket->sla_paused_at = null;
        $ticket->sla_remaining_minutes = null;
        $ticket->sla_escalated_at = null;
    }

    public function pause(Ticket $ticket, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        if ($ticket->sla_paused_at !== null || $ticket->sla_due_at === null) {
            return;
        }

        $ticket->sla_remaining_minutes = $this->calendar($now, $ticket->sla_due_at)
            ->businessMinutesBetween($now, $ticket->sla_due_at);
        $ticket->sla_paused_at = $now;
        $ticket->sla_due_at = null;
    }

    public function resume(Ticket $ticket, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        if ($ticket->sla_paused_at === null) {
            return;
        }

        $ticket->sla_due_at = $this->calendar($now)->addBusinessMinutes($now, (int) $ticket->sla_remaining_minutes);
        $ticket->sla_paused_at = null;
        $ticket->sla_remaining_minutes = null;
    }

    /**
     * Trạng thái SLA để hiển thị: none | running | paused | met | breached.
     *
     * @return array{state: string, due_at: ?CarbonInterface, remaining_minutes: ?int}
     */
    public function state(Ticket $ticket): array
    {
        if ($ticket->sla_paused_at !== null) {
            return ['state' => 'paused', 'due_at' => null, 'remaining_minutes' => (int) $ticket->sla_remaining_minutes];
        }

        $due = $ticket->sla_due_at;

        if ($due === null || $ticket->status === 'cancelled') {
            return ['state' => 'none', 'due_at' => $due, 'remaining_minutes' => null];
        }

        if ($ticket->resolved_at !== null) {
            return [
                'state' => $ticket->resolved_at->lessThanOrEqualTo($due) ? 'met' : 'breached',
                'due_at' => $due,
                'remaining_minutes' => null,
            ];
        }

        $now = now();

        if ($due->lessThan($now)) {
            return ['state' => 'breached', 'due_at' => $due, 'remaining_minutes' => 0];
        }

        return [
            'state' => 'running',
            'due_at' => $due,
            'remaining_minutes' => $this->calendar($now, $due)->businessMinutesBetween($now, $due),
        ];
    }

    /**
     * Quét phiếu quá hạn và leo thang (mỗi phiếu đúng một lần). Gọi bởi lệnh `sla:escalate`.
     *
     * @return int số phiếu đã leo thang
     */
    public function escalateOverdue(): int
    {
        $count = 0;
        $admins = $this->users->admins();

        foreach ($this->sla->overdueUnescalated(now()) as $ticket) {
            $escalated = DB::transaction(function () use ($ticket, $admins) {
                if (! $this->sla->markEscalated($ticket, now())) {
                    return false;
                }

                $this->sla->createEscalation([
                    'ticket_id' => $ticket->id,
                    'level' => 1,
                    'reason' => "Quá hạn SLA (mức {$ticket->priority}), hạn xử lý {$ticket->sla_due_at->format('d/m/Y H:i')}",
                ]);

                if ($actor = $admins->first()) {
                    $this->tickets->createHistory([
                        'ticket_id' => $ticket->id,
                        'user_id' => $actor->id,
                        'action' => 'sla_escalated',
                        'old_status' => $ticket->status,
                        'new_status' => $ticket->status,
                        'description' => 'Hệ thống leo thang: phiếu quá hạn SLA',
                    ]);
                }

                $this->notifications->ticketEvent(
                    $admins->concat([$ticket->assignedTo])->filter(),
                    $ticket,
                    'sla_escalated',
                    'Phiếu quá hạn SLA',
                    "Phiếu {$ticket->ticket_code} đã quá hạn xử lý và được leo thang.",
                );

                $this->audit->log('ticket.sla_escalated', null, $ticket, ['due_at' => $ticket->sla_due_at->toIso8601String()]);

                return true;
            });

            $count += $escalated ? 1 : 0;
        }

        return $count;
    }

    private function calendar(CarbonInterface $from, ?CarbonInterface $to = null): BusinessCalendar
    {
        $this->holidays ??= $this->sla->allHolidayDates();

        return new BusinessCalendar(
            (string) config('sla.timezone'),
            (array) config('sla.work_days'),
            (int) config('sla.work_start_minutes'),
            (int) config('sla.work_end_minutes'),
            $this->holidays,
            (int) config('sla.max_scan_days'),
        );
    }
}
