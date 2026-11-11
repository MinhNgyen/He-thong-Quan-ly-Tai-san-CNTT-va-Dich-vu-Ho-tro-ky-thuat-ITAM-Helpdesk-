<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use Illuminate\Support\Facades\DB;

/**
 * Truy vấn tổng hợp cho báo cáo (K7): JOIN nhiều bảng, hàm tổng hợp COUNT/AVG/SUM, điều kiện thời gian.
 * Tính ở tầng CSDL (GROUP BY) chứ không lặp ở tầng ứng dụng. Biểu thức ngày giờ khác nhau giữa MySQL và SQLite nên được tách ra.
 */
class ReportRepository
{
    /** Phiếu theo tháng trong N tháng gần nhất: tổng số, đã giải quyết/đóng, đã hủy. */
    public function ticketsByMonth(int $months = 12): array
    {
        $since = now()->startOfMonth()->subMonths($months - 1);
        $month = $this->monthExpr('created_at');
        $done = "'" . implode("','", TicketStatus::done()) . "'";

        return DB::table('tickets')
            ->selectRaw("{$month} AS month, COUNT(*) AS total, SUM(CASE WHEN status IN ({$done}) THEN 1 ELSE 0 END) AS resolved, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled")
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw($month))
            ->orderBy(DB::raw($month))
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** Theo danh mục sự cố: số phiếu và thời gian giải quyết trung bình (giờ) của phiếu đã giải quyết. */
    public function resolutionByCategory(): array
    {
        $minutes = $this->diffMinutesExpr('t.created_at', 't.resolved_at');

        return DB::table('tickets AS t')
            ->join('ticket_categories AS c', 'c.id', '=', 't.category_id')
            ->selectRaw("c.name AS category, COUNT(*) AS total, SUM(CASE WHEN t.resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS resolved, ROUND(AVG(CASE WHEN t.resolved_at IS NOT NULL THEN {$minutes} END) / 60.0, 1) AS avg_hours")
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** Tài sản theo danh mục và trạng thái, kèm tổng giá trị mua. */
    public function assetsByCategory(): array
    {
        return DB::table('assets AS a')
            ->join('asset_categories AS c', 'c.id', '=', 'a.category_id')
            ->selectRaw("c.name AS category, COUNT(*) AS total,
                SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) AS in_use,
                SUM(CASE WHEN a.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance,
                SUM(CASE WHEN a.status = 'retired' THEN 1 ELSE 0 END) AS retired,
                COALESCE(SUM(a.purchase_price), 0) AS total_value")
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** Tuân thủ SLA theo mức ưu tiên: đã giải quyết đúng hạn / trễ hạn / đang quá hạn. */
    public function slaCompliance(): array
    {
        return DB::table('tickets')
            ->selectRaw("priority,
                COUNT(*) AS total,
                SUM(CASE WHEN resolved_at IS NOT NULL AND sla_due_at IS NOT NULL AND resolved_at <= sla_due_at THEN 1 ELSE 0 END) AS met,
                SUM(CASE WHEN resolved_at IS NOT NULL AND sla_due_at IS NOT NULL AND resolved_at > sla_due_at THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN resolved_at IS NULL AND sla_due_at IS NOT NULL AND sla_due_at < ? AND status NOT IN ('closed','cancelled','waiting_user') THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN sla_escalated_at IS NOT NULL THEN 1 ELSE 0 END) AS escalated", [now()])
            ->groupBy('priority')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** Khối lượng công việc theo kỹ thuật viên. */
    public function technicianWorkload(): array
    {
        $done = "'" . implode("','", TicketStatus::done()) . "'";
        $active = "'" . implode("','", TicketStatus::active()) . "'";

        return DB::table('users AS u')
            ->join('roles AS r', 'r.id', '=', 'u.role_id')
            ->leftJoin('tickets AS t', 't.assigned_to', '=', 'u.id')
            ->where('r.name', 'Technician')
            ->selectRaw("u.name AS technician, COUNT(t.id) AS assigned, SUM(CASE WHEN t.status IN ({$active}) THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN t.status IN ({$done}) THEN 1 ELSE 0 END) AS resolved")
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('assigned')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** Chi phí bảo trì theo tháng (đợt đã hoàn thành). */
    public function maintenanceCostByMonth(int $months = 12): array
    {
        $month = $this->monthExpr('maintenance_date');

        return DB::table('maintenances')
            ->selectRaw("{$month} AS month, COUNT(*) AS total, COALESCE(SUM(cost), 0) AS cost")
            ->where('status', 'completed')
            ->where('maintenance_date', '>=', now()->startOfMonth()->subMonths($months - 1)->toDateString())
            ->groupBy(DB::raw($month))
            ->orderBy(DB::raw($month))
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function monthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function diffMinutesExpr(string $from, string $to): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "((julianday({$to}) - julianday({$from})) * 1440)"
            : "TIMESTAMPDIFF(MINUTE, {$from}, {$to})";
    }
}
