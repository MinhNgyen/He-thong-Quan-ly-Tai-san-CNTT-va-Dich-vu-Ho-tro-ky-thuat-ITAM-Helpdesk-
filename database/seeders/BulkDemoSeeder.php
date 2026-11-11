<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sinh dữ liệu giả lập LỚN để đo hiệu năng (mục 5.4): mặc định 5 000 phiếu và 5 000 tài sản.
 * Chạy:  php artisan db:seed --class=BulkDemoSeeder   (đổi số lượng: BULK_ROWS=20000)
 * Chỉ dùng trên môi trường thử nghiệm của nhóm; dữ liệu hoàn toàn giả lập.
 */
class BulkDemoSeeder extends Seeder
{
    public function run(): void
    {
        $rows = (int) env('BULK_ROWS', 5000);
        $users = User::pluck('id')->all();
        $technicians = User::whereHas('role', fn ($q) => $q->where('name', 'Technician'))->pluck('id')->all();
        $ticketCategories = TicketCategory::pluck('id')->all();
        $assetCategories = AssetCategory::pluck('id')->all();
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed', 'cancelled'];

        foreach (array_chunk(range(1, $rows), 500) as $chunk) {
            $assets = [];
            $tickets = [];

            foreach ($chunk as $i) {
                $created = now()->subDays(random_int(0, 365))->subMinutes(random_int(0, 1440));
                $status = $statuses[array_rand($statuses)];
                $resolved = in_array($status, ['resolved', 'closed'], true) ? (clone $created)->addHours(random_int(1, 72)) : null;
                $assigned = $status === 'open' ? null : $technicians[array_rand($technicians)];

                $assets[] = [
                    'asset_code' => sprintf('BULK-A%07d', $i),
                    'name' => 'Thiết bị mô phỏng ' . $i,
                    'category_id' => $assetCategories[array_rand($assetCategories)],
                    'serial_number' => sprintf('SN-BULK-%09d', $i),
                    'status' => ['available', 'in_use', 'maintenance', 'retired'][array_rand([0, 1, 2, 3])],
                    'created_at' => $created, 'updated_at' => $created,
                ];

                $tickets[] = [
                    'ticket_code' => sprintf('BULK-T%07d', $i),
                    'created_by' => $users[array_rand($users)],
                    'assigned_to' => $assigned,
                    'category_id' => $ticketCategories[array_rand($ticketCategories)],
                    'title' => 'Yêu cầu hỗ trợ mô phỏng ' . $i,
                    'description' => 'Nội dung mô phỏng phục vụ đo hiệu năng.',
                    'priority' => $priorities[array_rand($priorities)],
                    'status' => $status,
                    'resolved_at' => $resolved,
                    'closed_at' => $status === 'closed' ? $resolved : null,
                    'sla_due_at' => (clone $created)->addHours(random_int(2, 48)),
                    'created_at' => $created, 'updated_at' => $created,
                ];
            }

            DB::table('assets')->insertOrIgnore($assets);
            DB::table('tickets')->insertOrIgnore($tickets);
        }
    }
}
