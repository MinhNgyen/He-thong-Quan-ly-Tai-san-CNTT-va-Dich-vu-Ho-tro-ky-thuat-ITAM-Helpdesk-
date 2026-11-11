<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRepository
{
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /** Chỉ tìm trong thông báo của chính người dùng (chống IDOR). */
    public function findFor(User $user, string $id): ?DatabaseNotification
    {
        return $user->notifications()->whereKey($id)->first();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
