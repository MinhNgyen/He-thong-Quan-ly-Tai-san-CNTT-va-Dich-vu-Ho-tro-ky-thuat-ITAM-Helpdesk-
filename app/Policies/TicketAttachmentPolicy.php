<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Quyền mức đối tượng cho tệp đính kèm: kế thừa quyền xem/bình luận của phiếu cha (chống IDOR). */
class TicketAttachmentPolicy
{
    public function view(User $user, TicketAttachment $attachment): bool
    {
        return Gate::forUser($user)->allows('view', $attachment->ticket);
    }

    /** Chỉ người tải lên hoặc Admin được xóa, và phiếu chưa kết thúc. */
    public function delete(User $user, TicketAttachment $attachment): bool
    {
        return ($user->isAdmin() || (int) $attachment->user_id === (int) $user->id)
            && Gate::forUser($user)->allows('comment', $attachment->ticket);
    }
}
