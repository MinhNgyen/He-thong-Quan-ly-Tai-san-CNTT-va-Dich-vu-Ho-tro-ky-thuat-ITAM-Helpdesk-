<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm trạng thái "waiting_user" (chờ người dùng phản hồi) - lúc này đồng hồ SLA tạm dừng.
     * Dùng migration mới (không sửa migration gốc) để CSDL đã triển khai chỉ cần `php artisan migrate`.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed', 'cancelled'])
                ->default('open')
                ->change();
        });
    }

    public function down(): void
    {
        // Phiếu đang chờ người dùng quay về "in_progress" trước khi thu hẹp enum (tránh lỗi dữ liệu ngoài danh sách).
        DB::table('tickets')->where('status', 'waiting_user')->update(['status' => 'in_progress']);

        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('status', ['open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'])
                ->default('open')
                ->change();
        });
    }
};
