<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketHistory;

class TicketHistorySeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::where('email', 'employee@example.com')->firstOrFail();
        $technician = User::where('email', 'technician@example.com')->firstOrFail();

        $ticket1 = Ticket::where('ticket_code', 'YC001')->firstOrFail();
        $ticket2 = Ticket::where('ticket_code', 'YC002')->firstOrFail();
        $ticket3 = Ticket::where('ticket_code', 'YC004')->firstOrFail();

        TicketHistory::firstOrCreate([
            'ticket_id' => $ticket1->id,
            'user_id' => $employee->id,
            'action' => 'created',
            'old_status' => null,
            'new_status' => 'open',
            'description' => 'Nhan vien tao phieu ho tro.',
        ]);

        TicketHistory::firstOrCreate([
            'ticket_id' => $ticket1->id,
            'user_id' => $technician->id,
            'action' => 'assigned',
            'old_status' => 'open',
            'new_status' => 'assigned',
            'description' => 'Phieu duoc giao cho nhan vien ky thuat.',
        ]);

        TicketHistory::firstOrCreate([
            'ticket_id' => $ticket2->id,
            'user_id' => $technician->id,
            'action' => 'status_changed',
            'old_status' => 'assigned',
            'new_status' => 'in_progress',
            'description' => 'Ky thuat vien bat dau xu ly.',
        ]);

        TicketHistory::firstOrCreate([
            'ticket_id' => $ticket3->id,
            'user_id' => $technician->id,
            'action' => 'status_changed',
            'old_status' => 'in_progress',
            'new_status' => 'resolved',
            'description' => 'Phieu ho tro da duoc giai quyet.',
        ]);
    }
}
