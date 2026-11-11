<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class EscalateOverdueTickets extends Command
{
    protected $signature = 'sla:escalate';

    protected $description = 'Leo thang các phiếu đã quá hạn SLA (mỗi phiếu đúng một lần)';

    public function handle(SlaService $sla): int
    {
        $count = $sla->escalateOverdue();

        $this->info("Đã leo thang {$count} phiếu quá hạn SLA.");

        return self::SUCCESS;
    }
}
