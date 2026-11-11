<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chỉ mục cho các truy vấn nóng (xem mục 5.4 của báo cáo):
     * danh sách phiếu theo trạng thái/ngày, phạm vi theo người xử lý/người tạo, quét quá hạn SLA,
     * tài sản theo danh mục và trạng thái.
     *
     * Lưu ý MySQL: chỉ mục bắt đầu bằng cột khóa ngoại cũng phục vụ khóa ngoại đó. Vì vậy up() dọn chỉ mục đơn
     * thừa sau khi đã có chỉ mục tổng hợp, và down() phải tạo lại chỉ mục đơn TRƯỚC khi xóa chỉ mục tổng hợp
     * (nếu không MySQL báo "needed in a foreign key constraint").
     * Có thể chạy `migrate:rollback --step=1` rồi `migrate` nhiều lần để đo trước/sau (perf:benchmark).
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_tickets_status_created');
            $table->index(['assigned_to', 'status'], 'idx_tickets_assignee_status');
            $table->index(['created_by', 'created_at'], 'idx_tickets_creator_created');
            $table->index(['status', 'sla_due_at'], 'idx_tickets_sla_scan');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->index(['category_id', 'status'], 'idx_assets_category_status');
            $table->index('status', 'idx_assets_status');
        });

        foreach ([['tickets', 'tickets_assigned_to_index'], ['tickets', 'tickets_created_by_index'], ['assets', 'assets_category_id_index']] as [$tableName, $index]) {
            if (Schema::hasIndex($tableName, $index)) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($index));
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasIndex('tickets', 'tickets_assigned_to_index')) {
                $table->index('assigned_to');
            }
            if (! Schema::hasIndex('tickets', 'tickets_created_by_index')) {
                $table->index('created_by');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasIndex('assets', 'assets_category_id_index')) {
                $table->index('category_id');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_status_created');
            $table->dropIndex('idx_tickets_assignee_status');
            $table->dropIndex('idx_tickets_creator_created');
            $table->dropIndex('idx_tickets_sla_scan');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('idx_assets_category_status');
            $table->dropIndex('idx_assets_status');
        });
    }
};
