<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\AssetRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tầng nghiệp vụ cho phiếu hỗ trợ: tạo phiếu, bình luận, nhận việc, phân công / đổi trạng thái.
 * Việc "ai được làm gì" do TicketPolicy quyết định ở tầng HTTP; tại đây chỉ xử lý quy tắc dữ liệu.
 */
class TicketService
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly AssetRepository $assets,
        private readonly UserRepository $users,
        private readonly SlaService $sla,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * @param array{status?: ?string, q?: ?string} $filters
     */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return $this->tickets->paginateVisibleTo(
            $user,
            $this->blankToNull($filters['status'] ?? null),
            $this->blankToNull($filters['q'] ?? null),
        );
    }

    /**
     * Số liệu cho trang tổng quan, cùng phạm vi với danh sách phiếu; Admin/Technician có thêm dữ liệu điều phối.
     */
    public function dashboard(User $user): array
    {
        $data = $this->tickets->countsVisibleTo($user) + [
            'recent' => $this->tickets->recentVisibleTo($user),
        ];

        if ($user->isAdmin()) {
            $data += [
                'by_status' => $this->tickets->countByStatus(),
                'unassigned' => $this->tickets->unassigned(),
                'unassigned_count' => $this->tickets->unassignedCount(),
            ];
        }

        if ($user->isTechnician()) {
            $data += [
                'mine' => $this->tickets->activeAssignedTo($user),
                'mine_count' => $this->tickets->countActiveAssignedTo($user),
                'resolved_by_me' => $this->tickets->countResolvedBy($user),
                'unassigned' => $this->tickets->unassigned(),
                'unassigned_count' => $this->tickets->unassignedCount(),
            ];
        }

        return $data;
    }

    /** Dữ liệu cho form tạo phiếu. */
    public function createFormOptions(): array
    {
        return [
            'categories' => $this->tickets->activeCategories(),
            'assets' => $this->assets->assignable(),
        ];
    }

    public function loadDetail(Ticket $ticket): Ticket
    {
        return $this->tickets->loadDetail($ticket);
    }

    /** Trạng thái SLA của phiếu để hiển thị (none | running | paused | met | breached). */
    public function slaState(Ticket $ticket): array
    {
        return $this->sla->state($ticket);
    }

    public function assignableTechnicians(): Collection
    {
        return $this->users->assignableTechnicians();
    }

    public function create(User $creator, array $data): Ticket
    {
        return DB::transaction(function () use ($creator, $data) {
            $ticket = $this->tickets->create($data + [
                'ticket_code' => $this->generateCode(),
                'created_by' => $creator->id,
                'status' => TicketStatus::Open->value,
            ]);

            $this->sla->start($ticket);
            $this->tickets->save($ticket);

            $this->tickets->createHistory([
                'ticket_id' => $ticket->id,
                'user_id' => $creator->id,
                'action' => 'created',
                'new_status' => TicketStatus::Open->value,
                'description' => 'Tạo phiếu hỗ trợ',
            ]);

            $this->audit->log('ticket.created', $creator, $ticket, ['code' => $ticket->ticket_code, 'priority' => $ticket->priority]);
            $this->tickets->forgetStatusCounts();

            return $ticket;
        });
    }

    public function addComment(User $author, Ticket $ticket, string $content): void
    {
        DB::transaction(function () use ($author, $ticket, $content) {
            $this->tickets->lockForUpdate($ticket);

            $this->tickets->createComment([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'content' => $content,
            ]);

            $this->tickets->createHistory([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'action' => 'commented',
                'old_status' => $ticket->status,
                'new_status' => $ticket->status,
                'description' => 'Thêm bình luận',
            ]);

            // Người gửi phiếu phản hồi khi phiếu đang chờ họ: quay lại "Đang xử lý" và chạy tiếp đồng hồ SLA.
            if ($ticket->status === TicketStatus::WaitingUser->value && (int) $ticket->created_by === (int) $author->id) {
                $ticket->status = TicketStatus::InProgress->value;
                $this->sla->resume($ticket);
                $this->tickets->save($ticket);

                $this->tickets->createHistory([
                    'ticket_id' => $ticket->id,
                    'user_id' => $author->id,
                    'action' => 'user_replied',
                    'old_status' => TicketStatus::WaitingUser->value,
                    'new_status' => TicketStatus::InProgress->value,
                    'description' => 'Người dùng đã phản hồi, tiếp tục xử lý',
                ]);

                $this->tickets->forgetStatusCounts();
            }

            $other = (int) $author->id === (int) $ticket->created_by ? $ticket->assignedTo : $ticket->createdBy;
            $this->notifications->ticketEvent($other, $ticket, 'commented', 'Bình luận mới', "{$author->name} đã bình luận ở phiếu {$ticket->ticket_code}.", $author);

            $this->audit->log('ticket.commented', $author, $ticket);
        });
    }

    /** Technician tự nhận phiếu chưa có người xử lý. Trả về false nếu đã có người nhận trước. */
    public function claim(User $technician, Ticket $ticket): bool
    {
        return DB::transaction(function () use ($technician, $ticket) {
            if (! $this->tickets->claim($ticket, $technician)) {
                return false;
            }

            $this->tickets->createHistory([
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'action' => 'claimed',
                'old_status' => TicketStatus::Open->value,
                'new_status' => TicketStatus::Assigned->value,
                'description' => 'Tự nhận xử lý phiếu',
            ]);

            $this->notifications->ticketEvent($ticket->createdBy, $ticket, 'claimed', 'Phiếu đã có người nhận', "{$technician->name} đã nhận xử lý phiếu {$ticket->ticket_code}.", $technician);
            $this->audit->log('ticket.claimed', $technician, $ticket);
            $this->tickets->forgetStatusCounts();

            return true;
        });
    }

    /**
     * Đổi trạng thái và (nếu được phép) đổi người xử lý.
     *
     * @param array{status: string, assigned_to?: int|string|null} $data
     * @param bool $canAssign Người thao tác có quyền phân công (kết quả của TicketPolicy::assign)
     *
     * @throws ValidationException khi dữ liệu phân công không hợp lệ
     */
    public function update(User $actor, Ticket $ticket, array $data, bool $canAssign): Ticket
    {
        return DB::transaction(function () use ($actor, $ticket, $data, $canAssign) {
            // Khóa dòng để hai người cập nhật đồng thời được xếp thành chuỗi (không ghi đè lẫn nhau).
            $this->tickets->lockForUpdate($ticket);

            // Người không có quyền phân công không được đổi người xử lý: giữ nguyên giá trị cũ.
            $newAssignee = $canAssign ? ($data['assigned_to'] ?? null) : $ticket->assigned_to;
            $newAssignee = ($newAssignee === null || $newAssignee === '') ? null : (int) $newAssignee;

            if ($canAssign && $newAssignee !== null && ! $this->users->isTechnician($newAssignee)) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Chỉ được phân công cho Technician.',
                ]);
            }

            $current = TicketStatus::from($ticket->status);
            $target = TicketStatus::from($data['status']);

            if (! $current->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Không thể chuyển phiếu từ \"{$current->label()}\" sang \"{$target->label()}\".",
                ]);
            }

            $needsAssignee = in_array($target, [
                TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::WaitingUser, TicketStatus::Resolved, TicketStatus::Closed,
            ], true);

            if ($needsAssignee && $newAssignee === null) {
                throw ValidationException::withMessages([
                    'assigned_to' => "Cần chọn nhân viên xử lý khi trạng thái là \"{$target->label()}\".",
                ]);
            }

            if ($target === TicketStatus::Open && $newAssignee !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Phiếu đang có người xử lý, hãy bỏ chọn nhân viên nếu muốn đưa về trạng thái Mới.',
                ]);
            }

            $oldStatus = $ticket->status;
            $oldAssignee = $ticket->assigned_to === null ? null : (int) $ticket->assigned_to;

            $ticket->assigned_to = $newAssignee;
            $this->applyStatus($ticket, $data['status']);

            // Đồng hồ SLA: tạm dừng khi chờ người dùng, chạy tiếp khi quay lại xử lý.
            if ($target === TicketStatus::WaitingUser && $current !== TicketStatus::WaitingUser) {
                $this->sla->pause($ticket);
            } elseif ($current === TicketStatus::WaitingUser && $target !== TicketStatus::WaitingUser && ! $target->isFinal()) {
                $this->sla->resume($ticket);
            }

            $this->tickets->save($ticket);

            if ($oldStatus !== $ticket->status || $oldAssignee !== $newAssignee) {
                $this->tickets->createHistory([
                    'ticket_id' => $ticket->id,
                    'user_id' => $actor->id,
                    'action' => $oldAssignee !== $newAssignee ? 'assigned_or_reassigned' : 'status_changed',
                    'old_status' => $oldStatus,
                    'new_status' => $ticket->status,
                    'description' => 'Cập nhật phân công/trạng thái',
                ]);

                $this->audit->log(
                    $oldAssignee !== $newAssignee ? 'ticket.assigned' : 'ticket.status_changed',
                    $actor,
                    $ticket,
                    ['from' => $oldStatus, 'to' => $ticket->status, 'assignee' => $newAssignee],
                );

                if ($oldAssignee !== $newAssignee && $newAssignee !== null) {
                    $this->notifications->ticketEvent($ticket->assignedTo()->first(), $ticket, 'assigned', 'Bạn được phân công phiếu', "Phiếu {$ticket->ticket_code} được giao cho bạn xử lý.", $actor);
                }

                if ($oldStatus !== $ticket->status) {
                    $body = $target === TicketStatus::WaitingUser
                        ? "Phiếu {$ticket->ticket_code} đang chờ bạn phản hồi."
                        : "Phiếu {$ticket->ticket_code} chuyển sang \"{$target->label()}\".";
                    $this->notifications->ticketEvent($ticket->createdBy()->first(), $ticket, 'status_changed', 'Phiếu thay đổi trạng thái', $body, $actor);
                }
            }

            $this->tickets->forgetStatusCounts();

            return $ticket;
        });
    }

    /** Gán trạng thái mới và cập nhật mốc thời gian giải quyết / đóng phiếu tương ứng. */
    private function applyStatus(Ticket $ticket, string $status): void
    {
        $ticket->status = $status;
        $ticket->resolved_at = in_array($status, TicketStatus::done(), true)
            ? ($ticket->resolved_at ?? now())
            : null;
        $ticket->closed_at = $status === TicketStatus::Closed->value
            ? ($ticket->closed_at ?? now())
            : null;
    }

    private function generateCode(): string
    {
        do {
            $code = 'HD-' . now()->format('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->tickets->codeExists($code));

        return $code;
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
