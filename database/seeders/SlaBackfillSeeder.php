<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Services\SlaService;
use Illuminate\Database\Seeder;

/** Gán hạn SLA cho các phiếu mẫu chưa có (idempotent: chỉ phiếu chưa có hạn và không đang tạm dừng). */
class SlaBackfillSeeder extends Seeder
{
    public function run(SlaService $sla): void
    {
        Ticket::whereNull('sla_due_at')->whereNull('sla_paused_at')->each(function (Ticket $ticket) use ($sla) {
            $sla->start($ticket);
            $ticket->save();
        });
    }
}
