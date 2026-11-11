<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Thông báo trong ứng dụng (K9) cho các sự kiện nghiệp vụ quan trọng của phiếu hỗ trợ. */
class TicketEvent extends Notification
{
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
