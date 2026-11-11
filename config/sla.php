<?php

return [
    // Múi giờ dùng để xác định giờ làm việc (dữ liệu thời gian vẫn lưu theo UTC).
    'timezone' => env('SLA_TIMEZONE', 'Asia/Ho_Chi_Minh'),

    // Ngày làm việc theo ISO: 1 = Thứ Hai ... 7 = Chủ Nhật.
    'work_days' => [1, 2, 3, 4, 5],

    // Giờ làm việc: 08:00 - 17:00 (tính bằng phút kể từ 00:00).
    'work_start_minutes' => 8 * 60,
    'work_end_minutes' => 17 * 60,

    // Giá trị dự phòng (phút làm việc) khi bảng sla_policies chưa có dòng cho mức ưu tiên.
    'default_minutes' => [
        'urgent' => 120,
        'high' => 240,
        'medium' => 480,
        'low' => 1440,
    ],

    // Số ngày tối đa dò tiến về phía trước khi cộng giờ làm việc (chặn vòng lặp vô hạn).
    'max_scan_days' => 3660,

    // Khóa bí mật cho điểm cuối nội bộ POST /api/v1/internal/sla-escalate (hosting không có cron/dòng lệnh:
    // dịch vụ lập lịch bên ngoài gọi mỗi 5 phút kèm tiêu đề X-Cron-Key). Để trống = tắt điểm cuối (trả 404).
    'cron_key' => env('SLA_CRON_KEY'),
];
