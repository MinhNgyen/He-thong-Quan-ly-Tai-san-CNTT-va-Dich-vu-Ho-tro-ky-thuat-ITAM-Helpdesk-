<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEvent;
use App\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function __construct(private readonly NotificationRepository $notifications)
    {
    }

    /**
     * Gửi thông báo, bỏ qua người thao tác (không tự báo cho chính mình) và các giá trị rỗng.
     *
     * @param iterable<User|null>|User|null $recipients
     */
    public function ticketEvent(iterable|User|null $recipients, Ticket $ticket, string $type, string $title, string $body, ?User $actor = null): void
    {
        $recipients = $recipients instanceof User || $recipients === null ? [$recipients] : $recipients;

        $targets = collect($recipients)
            ->filter()
            ->reject(fn (User $user) => $actor && $user->is($actor))
            ->unique('id')
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        Notification::send($targets, new TicketEvent($type, $title, $body, route('tickets.show', $ticket)));
    }

    public function paginateFor(User $user): LengthAwarePaginator
    {
        return $this->notifications->paginateFor($user);
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCount($user);
    }

    /** Đánh dấu đã đọc và trả về URL đích (null nếu không tìm thấy). */
    public function markRead(User $user, string $id): ?string
    {
        $notification = $this->notifications->findFor($user, $id);

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        return $notification->data['url'] ?? route('notifications.index');
    }

    public function markAllRead(User $user): void
    {
        $this->notifications->markAllRead($user);
    }
}
