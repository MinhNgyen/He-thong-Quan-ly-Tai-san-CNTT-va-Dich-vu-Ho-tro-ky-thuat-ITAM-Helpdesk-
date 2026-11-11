<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /** Ngày nghỉ minh họa (dữ liệu mô phỏng); quản trị chỉnh theo lịch nghỉ chính thức khi vận hành. */
    public function run(): void
    {
        $days = [
            ['2026-09-02', 'Quốc khánh'],
            ['2027-01-01', 'Tết Dương lịch'],
            ['2027-02-05', 'Tết Nguyên đán (minh họa)'],
            ['2027-02-08', 'Tết Nguyên đán (minh họa)'],
            ['2027-02-09', 'Tết Nguyên đán (minh họa)'],
            ['2027-04-30', 'Ngày Giải phóng miền Nam'],
            ['2027-05-01', 'Quốc tế Lao động'],
            ['2027-09-02', 'Quốc khánh'],
        ];

        foreach ($days as [$date, $name]) {
            Holiday::updateOrCreate(['holiday_date' => $date], ['name' => $name]);
        }
    }
}
