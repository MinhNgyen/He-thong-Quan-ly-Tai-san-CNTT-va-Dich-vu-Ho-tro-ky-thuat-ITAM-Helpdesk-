<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

/**
 * Quyền MỨC 2 (trên đúng đối tượng) cho phiếu hỗ trợ.
 * Quyền mức 1 (chức năng) do middleware `role:` và `auth` đảm nhiệm.
 */
class TicketPolicy
{
    /** Xem chi tiết phiếu. */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || (int) $ticket->created_by === (int) $user->id) {
            return true;
        }

        return $user->isTechnician()
            && ($ticket->assigned_to === null || (int) $ticket->assigned_to === (int) $user->id);
    }

    /** Bình luận: ai xem được thì bình luận được, trừ phiếu đã đóng/hủy. */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && ! $this->isFinal($ticket);
    }

    /**
     * Đổi trạng thái / phân công qua form cập nhật:
     * Admin điều phối mọi phiếu; Technician chỉ xử lý phiếu được gán cho mình; phiếu đã đóng/hủy thì khóa.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($this->isFinal($ticket)) {
            return false;
        }

        return $user->isAdmin()
            || ($user->isTechnician() && (int) $ticket->assigned_to === (int) $user->id);
    }

    /** Phân công / chuyển giao cho người khác: chỉ Admin. */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    /** Technician tự nhận phiếu chưa có người xử lý. */
    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->isTechnician()
            && $ticket->assigned_to === null
            && $ticket->status === TicketStatus::Open->value;
    }

    private function isFinal(Ticket $ticket): bool
    {
        return TicketStatus::tryFrom((string) $ticket->status)?->isFinal() ?? false;
    }
}
