<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    /** Thời gian cam kết xử lý tính bằng PHÚT LÀM VIỆC (giờ hành chính 08:00-17:00, thứ Hai-thứ Sáu). */
    public function run(): void
    {
        $policies = [
            ['urgent', 'Khẩn cấp - xử lý trong 2 giờ làm việc', 120],
            ['high', 'Cao - xử lý trong 4 giờ làm việc', 240],
            ['medium', 'Trung bình - xử lý trong 8 giờ làm việc (1 ngày)', 480],
            ['low', 'Thấp - xử lý trong 24 giờ làm việc (3 ngày)', 1440],
        ];

        foreach ($policies as [$priority, $name, $minutes]) {
            SlaPolicy::updateOrCreate(['priority' => $priority], ['name' => $name, 'resolution_minutes' => $minutes]);
        }
    }
}
