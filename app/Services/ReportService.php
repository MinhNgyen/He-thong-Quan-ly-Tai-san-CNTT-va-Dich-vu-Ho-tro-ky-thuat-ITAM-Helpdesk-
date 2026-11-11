<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /** key => [tiêu đề, phương thức repository, [cột => nhãn]] */
    private const REPORTS = [
        'tickets-by-month' => ['Phiếu hỗ trợ theo tháng', 'ticketsByMonth', ['month' => 'Tháng', 'total' => 'Tổng phiếu', 'resolved' => 'Đã giải quyết', 'cancelled' => 'Đã hủy']],
        'resolution-by-category' => ['Thời gian giải quyết theo danh mục', 'resolutionByCategory', ['category' => 'Danh mục', 'total' => 'Số phiếu', 'resolved' => 'Đã giải quyết', 'avg_hours' => 'TB giờ giải quyết']],
        'assets-by-category' => ['Tài sản theo danh mục và trạng thái', 'assetsByCategory', ['category' => 'Danh mục', 'total' => 'Tổng', 'available' => 'Sẵn sàng', 'in_use' => 'Đang dùng', 'maintenance' => 'Bảo trì', 'retired' => 'Ngừng dùng', 'total_value' => 'Tổng giá trị mua']],
        'sla-compliance' => ['Tuân thủ SLA theo mức ưu tiên', 'slaCompliance', ['priority' => 'Mức ưu tiên', 'total' => 'Tổng', 'met' => 'Đúng hạn', 'late' => 'Trễ hạn', 'overdue' => 'Đang quá hạn', 'escalated' => 'Đã leo thang']],
        'technician-workload' => ['Khối lượng công việc kỹ thuật viên', 'technicianWorkload', ['technician' => 'Kỹ thuật viên', 'assigned' => 'Được giao', 'active' => 'Đang xử lý', 'resolved' => 'Đã giải quyết']],
        'maintenance-cost' => ['Chi phí bảo trì theo tháng', 'maintenanceCostByMonth', ['month' => 'Tháng', 'total' => 'Số đợt', 'cost' => 'Chi phí']],
    ];

    public function __construct(
        private readonly ReportRepository $reports,
        private readonly AuditService $audit,
    ) {
    }

    /** @return array<string, array{title: string, columns: array<string, string>, rows: list<array<string, mixed>>}> */
    public function all(): array
    {
        $result = [];

        foreach (array_keys(self::REPORTS) as $key) {
            $result[$key] = $this->build($key);
        }

        return $result;
    }

    public function exists(string $key): bool
    {
        return isset(self::REPORTS[$key]);
    }

    /** @return array{title: string, columns: array<string, string>, rows: list<array<string, mixed>>} */
    public function build(string $key): array
    {
        [$title, $method, $columns] = self::REPORTS[$key];

        return ['title' => $title, 'columns' => $columns, 'rows' => $this->reports->{$method}()];
    }

    /** Xuất CSV (UTF-8 có BOM để Excel đọc đúng tiếng Việt). */
    public function exportCsv(string $key): StreamedResponse
    {
        $report = $this->build($key);
        $this->audit->log('report.exported', null, null, ['report' => $key, 'rows' => count($report['rows'])]);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_values($report['columns']));

            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map(
                    fn (string $column) => $this->safeCell($row[$column] ?? ''),
                    array_keys($report['columns']),
                ));
            }

            fclose($out);
        }, "bao-cao-{$key}-" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Chặn "CSV injection": ô bắt đầu bằng = + - @ sẽ bị Excel hiểu là công thức. */
    private function safeCell(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($text)
            ? "'" . $text
            : $text;
    }
}
