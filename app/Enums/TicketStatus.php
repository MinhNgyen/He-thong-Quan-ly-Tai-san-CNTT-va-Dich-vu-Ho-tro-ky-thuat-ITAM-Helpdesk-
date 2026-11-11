<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingUser = 'waiting_user';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Mới',
            self::Assigned => 'Đã phân công',
            self::InProgress => 'Đang xử lý',
            self::WaitingUser => 'Chờ người dùng phản hồi',
            self::Resolved => 'Đã giải quyết',
            self::Closed => 'Đã đóng',
            self::Cancelled => 'Đã hủy',
        };
    }

    /**
     * Các trạng thái được phép chuyển tới từ trạng thái hiện tại.
     *
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Open => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Open, self::InProgress, self::Cancelled],
            self::InProgress => [self::Assigned, self::WaitingUser, self::Resolved, self::Cancelled],
            self::WaitingUser => [self::InProgress, self::Cancelled],
            self::Resolved => [self::InProgress, self::Closed],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return $this === $to || in_array($to, $this->next(), true);
    }

    /** Đồng hồ SLA chỉ chạy khi phiếu đang chờ IT xử lý (không tính lúc chờ người dùng và khi đã xong). */
    public function slaRunning(): bool
    {
        return in_array($this, [self::Open, self::Assigned, self::InProgress], true);
    }

    /** Phiếu đã kết thúc: không còn sửa hay bình luận được. */
    public function isFinal(): bool
    {
        return $this === self::Closed || $this === self::Cancelled;
    }

    /**
     * Lựa chọn hiển thị trong form cập nhật: trạng thái hiện tại + các trạng thái hợp lệ kế tiếp.
     *
     * @return array<string, string> value => nhãn
     */
    public function options(): array
    {
        $options = [$this->value => $this->label()];

        foreach ($this->next() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Phiếu đang chờ hoặc đang được xử lý.
     *
     * @return list<string>
     */
    public static function active(): array
    {
        return [self::Open->value, self::Assigned->value, self::InProgress->value, self::WaitingUser->value];
    }

    /**
     * Phiếu đã được giải quyết hoặc đã đóng.
     *
     * @return list<string>
     */
    public static function done(): array
    {
        return [self::Resolved->value, self::Closed->value];
    }
}
