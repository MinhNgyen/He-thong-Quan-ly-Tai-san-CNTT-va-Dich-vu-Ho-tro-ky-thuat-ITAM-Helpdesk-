<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Quét phiếu quá hạn SLA mỗi 5 phút. Trên hosting không có dòng lệnh: cấu hình cron của nhà cung cấp gọi `php artisan schedule:run` mỗi phút,
// hoặc dùng trình lập lịch gọi lệnh `php artisan sla:escalate`.
Schedule::command('sla:escalate')->everyFiveMinutes()->withoutOverlapping();
