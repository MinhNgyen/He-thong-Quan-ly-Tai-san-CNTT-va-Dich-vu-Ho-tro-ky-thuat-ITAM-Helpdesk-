-- Kế hoạch thực thi các truy vấn nóng (mục 5.4). Chạy trên MySQL:
--   docker compose exec mysql mysql -u itam -p itam_helpdesk -t < docs/perf/explain.sql
-- Chụp ảnh kết quả TRƯỚC khi tạo chỉ mục (php artisan migrate:rollback --step=1) và SAU khi tạo (php artisan migrate).
-- Cột cần so sánh: type (ALL = quét toàn bảng; ref/range = dùng chỉ mục), key (chỉ mục được chọn), rows (số dòng ước tính).

-- 1. Danh sách phiếu theo trạng thái, mới nhất trước (trang Quản lý phiếu / API /tickets?status=)
EXPLAIN SELECT * FROM tickets WHERE status = 'in_progress' ORDER BY created_at DESC LIMIT 10;

-- 2. Số phiếu đang xử lý của một kỹ thuật viên (phạm vi quyền Technician, bảng điều khiển)
EXPLAIN SELECT COUNT(*) FROM tickets WHERE assigned_to = 2 AND status IN ('assigned', 'in_progress');

-- 3. Phiếu của một nhân viên, mới nhất trước (phạm vi quyền Employee)
EXPLAIN SELECT * FROM tickets WHERE created_by = 3 ORDER BY created_at DESC LIMIT 10;

-- 4. Tài sản theo trạng thái (lọc trang Tài sản IT, thống kê bảng điều khiển)
EXPLAIN SELECT category_id, COUNT(*) FROM assets WHERE status = 'available' GROUP BY category_id;

-- 5. Quét phiếu quá hạn SLA (lệnh sla:escalate). Lưu ý: nếu phần lớn phiếu thỏa điều kiện, MySQL có thể
--    chủ động chọn quét toàn bảng; hãy mô tả đúng kết quả quan sát được, không khẳng định chỉ mục giúp ích khi không thấy.
EXPLAIN SELECT * FROM tickets
 WHERE status IN ('open', 'assigned', 'in_progress') AND sla_due_at IS NOT NULL AND sla_due_at < NOW() AND sla_escalated_at IS NULL;

-- 6. Cây tài sản: truy vấn đệ quy (CTE) lấy toàn bộ tài sản con cháu của tài sản số 1
EXPLAIN WITH RECURSIVE tree AS (
    SELECT id, parent_id, 1 AS depth FROM assets WHERE parent_id = 1
    UNION ALL
    SELECT a.id, a.parent_id, t.depth + 1 FROM assets a INNER JOIN tree t ON a.parent_id = t.id WHERE t.depth < 50
) SELECT * FROM tree;
