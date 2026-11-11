<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Chặn chạy test khi cấu hình đang trỏ vào database thật:
     * RefreshDatabase sẽ xóa sạch dữ liệu (migrate:fresh) của database đó.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app->make('config');
        $default = $config->get('database.default');

        // Ngoại lệ an toàn: cho phép kiểm thử chéo trên MySQL khi tên CSDL kết thúc bằng "_test" (không bao giờ là CSDL thật).
        $isDedicatedTestMysql = $default === 'mysql' && str_ends_with((string) $config->get('database.connections.mysql.database'), '_test');

        if ($default !== 'sqlite' && ! $isDedicatedTestMysql) {
            throw new RuntimeException(
                'Từ chối chạy test: database.default là "' . $app->make('config')->get('database.default')
                . '" (không phải sqlite hoặc MySQL có tên kết thúc _test). Kiểm tra compose.yaml/.env không đặt DB_CONNECTION, rồi chạy lại.'
            );
        }

        return $app;
    }
}
