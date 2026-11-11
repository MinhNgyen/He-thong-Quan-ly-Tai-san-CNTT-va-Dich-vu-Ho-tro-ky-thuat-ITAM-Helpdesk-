<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tầng truy cập dữ liệu cho phiếu hỗ trợ: chỉ chứa truy vấn, không chứa quy tắc nghiệp vụ.
 */
class TicketRepository
{
    private const LIST_RELATIONS = ['category', 'createdBy', 'assignedTo', 'asset'];

    public function paginateVisibleTo(User $user, ?string $status, ?string $term, int $perPage = 10): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(self::LIST_RELATIONS)
            ->visibleTo($user);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($term !== null) {
            $query->search($term);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /** @return array{total: int, open: int, resolved: int} */
    public function countsVisibleTo(User $user): array
    {
        $base = fn () => Ticket::query()->visibleTo($user);

        return [
            'total' => $base()->count(),
            'open' => $base()->whereIn('status', TicketStatus::active())->count(),
            'resolved' => $base()->whereIn('status', TicketStatus::done())->count(),
        ];
    }

    public function recentVisibleTo(User $user, int $limit = 5): Collection
    {
        return Ticket::query()
            ->with(['category', 'createdBy', 'assignedTo'])
            ->visibleTo($user)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /** Phiếu mới chưa có người xử lý (dùng cho Admin phân công / Technician nhận việc). */
    public function unassigned(int $limit = 5): Collection
    {
        return Ticket::query()
            ->with(['category', 'createdBy'])
            ->whereNull('assigned_to')
            ->where('status', TicketStatus::Open->value)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unassignedCount(): int
    {
        return Ticket::whereNull('assigned_to')->where('status', TicketStatus::Open->value)->count();
    }

    /** Phiếu đang xử lý được gán cho một Technician. */
    public function activeAssignedTo(User $technician, int $limit = 6): Collection
    {
        return Ticket::query()
            ->with(['category', 'createdBy'])
            ->where('assigned_to', $technician->id)
            ->whereIn('status', [TicketStatus::Assigned->value, TicketStatus::InProgress->value])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function countActiveAssignedTo(User $technician): int
    {
        return Ticket::where('assigned_to', $technician->id)
            ->whereIn('status', [TicketStatus::Assigned->value, TicketStatus::InProgress->value])
            ->count();
    }

    public function countResolvedBy(User $technician): int
    {
        return Ticket::where('assigned_to', $technician->id)
            ->whereIn('status', TicketStatus::done())
            ->count();
    }

    public const CACHE_STATUS_COUNTS = 'tickets.status_counts';

    /**
     * Số phiếu theo trạng thái cho bảng điều khiển Admin. Có bộ nhớ đệm 60 giây;
     * mọi thao tác ghi phiếu gọi forgetStatusCounts() để làm mất hiệu lực ngay.
     *
     * @return array<string, int> mọi trạng thái đều có mặt (mặc định 0)
     */
    public function countByStatus(): array
    {
        return Cache::remember(self::CACHE_STATUS_COUNTS, 60, function () {
            $counts = Ticket::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $result = [];
            foreach (TicketStatus::cases() as $status) {
                $result[$status->value] = (int) ($counts[$status->value] ?? 0);
            }

            return $result;
        });
    }

    public function forgetStatusCounts(): void
    {
        Cache::forget(self::CACHE_STATUS_COUNTS);
    }

    /**
     * Khóa dòng phiếu trong giao dịch (SELECT ... FOR UPDATE) và nạp lại thuộc tính mới nhất vào $ticket,
     * để hai người cập nhật đồng thời được xếp thành chuỗi thay vì ghi đè lẫn nhau.
     */
    public function lockForUpdate(Ticket $ticket): Ticket
    {
        $fresh = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
        $ticket->setRawAttributes($fresh->getAttributes(), true);

        return $ticket;
    }

    public function loadDetail(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'category',
            'createdBy.department',
            'assignedTo',
            'asset',
            'comments.user',
            'histories.user',
            'attachments.user',
            'escalations',
        ]);
    }

    public function activeCategories(): Collection
    {
        return TicketCategory::where('is_active', true)->orderBy('name')->get();
    }

    public function codeExists(string $code): bool
    {
        return Ticket::where('ticket_code', $code)->exists();
    }

    public function create(array $attributes): Ticket
    {
        return Ticket::create($attributes);
    }

    public function save(Ticket $ticket): void
    {
        $ticket->save();
    }

    /**
     * Nhận phiếu theo kiểu nguyên tử: chỉ thành công nếu phiếu vẫn còn mở và chưa có người xử lý.
     */
    public function claim(Ticket $ticket, User $technician): bool
    {
        $affected = Ticket::whereKey($ticket->id)
            ->whereNull('assigned_to')
            ->where('status', TicketStatus::Open->value)
            ->update([
                'assigned_to' => $technician->id,
                'status' => TicketStatus::Assigned->value,
                'updated_at' => now(),
            ]);

        return $affected > 0;
    }

    public function createComment(array $attributes): TicketComment
    {
        return TicketComment::create($attributes);
    }

    public function createHistory(array $attributes): TicketHistory
    {
        return TicketHistory::create($attributes);
    }
}
