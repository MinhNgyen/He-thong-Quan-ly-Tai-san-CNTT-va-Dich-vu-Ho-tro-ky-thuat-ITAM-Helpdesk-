<?php

namespace App\Support;

use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\TicketCategory;

/**
 * Khai báo các danh mục quản trị (K4). Thêm danh mục mới chỉ cần thêm một dòng ở đây:
 * controller, service, repository và view dùng chung theo cấu hình.
 */
class CatalogRegistry
{
    /** @return array<string, array{model: class-string, title: string, table: string, usage: string, boolean?: string}> */
    public static function all(): array
    {
        return [
            'departments' => ['model' => Department::class, 'title' => 'Phòng ban', 'table' => 'departments', 'usage' => 'users'],
            'asset-categories' => ['model' => AssetCategory::class, 'title' => 'Danh mục tài sản', 'table' => 'asset_categories', 'usage' => 'assets'],
            'ticket-categories' => ['model' => TicketCategory::class, 'title' => 'Danh mục sự cố', 'table' => 'ticket_categories', 'usage' => 'tickets', 'boolean' => 'is_active'],
        ];
    }

    /** @return array{model: class-string, title: string, table: string, usage: string, boolean?: string} */
    public static function get(string $type): array
    {
        return self::all()[$type] ?? abort(404);
    }
}
