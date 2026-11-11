<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case InUse = 'in_use';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Sẵn sàng',
            self::InUse => 'Đang sử dụng',
            self::Maintenance => 'Bảo trì',
            self::Retired => 'Ngừng sử dụng',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Tài sản có thể gắn vào một phiếu hỗ trợ.
     *
     * @return list<string>
     */
    public static function assignable(): array
    {
        return [self::Available->value, self::InUse->value];
    }
}
