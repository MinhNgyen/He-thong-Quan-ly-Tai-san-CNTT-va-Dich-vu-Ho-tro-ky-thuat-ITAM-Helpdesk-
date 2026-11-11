<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Đo thời gian phản hồi của các điểm cuối "nóng" (mục 5.4): chạy N lần, báo cáo min / trung vị / p95 / max và số truy vấn SQL.
 * Chạy TRƯỚC và SAU khi tối ưu (ví dụ `php artisan migrate:rollback --step=1` để bỏ chỉ mục, rồi `migrate` để thêm lại)
 * và dùng --cold để bỏ bộ nhớ đệm giữa các lần đo. Chỉ dùng trên môi trường thử nghiệm của nhóm.
 */
class PerfBenchmark extends Command
{
    protected $signature = 'perf:benchmark {--runs=30 : Số lần đo mỗi điểm cuối} {--cold : Xóa bộ nhớ đệm trước mỗi lần đo} {--markdown : In dạng bảng Markdown}';

    protected $description = 'Đo thời gian phản hồi 6 điểm cuối nóng (trước/sau tối ưu)';

    /** @var array<string, array{0: string, 1: string}> tên => [phương thức, đường dẫn] */
    private const ENDPOINTS = [
        'GET /api/v1/tickets (phân trang)' => ['GET', '/api/v1/tickets'],
        'GET /api/v1/tickets?status=in_progress&q=mô phỏng' => ['GET', '/api/v1/tickets?status=in_progress&q=m%C3%B4%20ph%E1%BB%8Fng'],
        'GET /api/v1/assets?status=available' => ['GET', '/api/v1/assets?status=available'],
        'GET / (bảng điều khiển Admin)' => ['GET', '/'],
        'GET /reports (6 báo cáo tổng hợp)' => ['GET', '/reports'],
        'GET /admin/audit-logs' => ['GET', '/admin/audit-logs'],
    ];

    public function handle(): int
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'Admin'))->first();

        if (! $admin) {
            $this->error('Chưa có tài khoản Admin. Hãy chạy: php artisan db:seed');

            return self::FAILURE;
        }

        $runs = max(1, (int) $this->option('runs'));
        $rows = [];

        // Token API tạm thời (chỉ lưu hash SHA-256 như token thật), xóa sau khi đo.
        $plain = Str::random(48);
        $apiToken = ApiToken::create(['user_id' => $admin->id, 'name' => 'perf-benchmark', 'token' => hash('sha256', $plain), 'expires_at' => now()->addHour()]);

        // Đăng ký bộ đếm truy vấn MỘT lần; mỗi lần đo đặt lại về 0.
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        foreach (self::ENDPOINTS as $name => [$method, $uri]) {
            $times = [];
            $queries = 0;
            $status = 0;

            for ($i = 0; $i < $runs + 1; $i++) { // lần đầu là khởi động, bỏ khỏi thống kê
                if ($this->option('cold')) {
                    Cache::flush();
                }

                Auth::guard('web')->setUser($admin);
                $count = 0;

                $start = hrtime(true);
                $response = app()->handle(Request::create($uri, $method, server: [
                    'HTTP_ACCEPT' => str_starts_with($uri, '/api/') ? 'application/json' : 'text/html',
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $plain,
                ]));
                $elapsed = (hrtime(true) - $start) / 1e6;
                $status = $response->getStatusCode();

                if ($i > 0) {
                    $times[] = $elapsed;
                    $queries = $count;
                }
            }

            sort($times);
            $rows[] = [
                $name,
                $status,
                number_format($times[0], 1),
                number_format($times[(int) floor(count($times) / 2)], 1),
                number_format($times[(int) min(count($times) - 1, ceil(count($times) * 0.95) - 1)], 1),
                number_format(end($times), 1),
                $queries,
            ];
        }

        $apiToken->delete();

        $headers = ['Điểm cuối', 'HTTP', 'Min (ms)', 'Trung vị (ms)', 'p95 (ms)', 'Max (ms)', 'Số truy vấn SQL'];
        $dataset = sprintf('%d phiếu, %d tài sản, %d nhật ký', DB::table('tickets')->count(), DB::table('assets')->count(), DB::table('audit_logs')->count());

        $this->line("Dữ liệu: {$dataset} · {$runs} lần/điểm cuối · " . ($this->option('cold') ? 'xóa cache mỗi lần' : 'cache ấm') . ' · CSDL: ' . DB::connection()->getDriverName());

        if ($this->option('markdown')) {
            $this->line('| ' . implode(' | ', $headers) . ' |');
            $this->line('|' . str_repeat('---|', count($headers)));
            foreach ($rows as $row) {
                $this->line('| ' . implode(' | ', $row) . ' |');
            }
        } else {
            $this->table($headers, $rows);
        }

        return self::SUCCESS;
    }
}
