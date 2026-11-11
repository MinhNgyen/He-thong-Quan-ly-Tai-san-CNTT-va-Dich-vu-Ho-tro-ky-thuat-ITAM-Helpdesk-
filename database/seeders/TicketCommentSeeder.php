<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketComment;

class TicketCommentSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::where('email', 'employee@example.com')->firstOrFail();
        $technician = User::where('email', 'technician@example.com')->firstOrFail();

        $ticket1 = Ticket::where('ticket_code', 'YC001')->firstOrFail();
        $ticket2 = Ticket::where('ticket_code', 'YC002')->firstOrFail();
        $ticket3 = Ticket::where('ticket_code', 'YC004')->firstOrFail();

        TicketComment::firstOrCreate([
            'ticket_id' => $ticket1->id,
            'user_id' => $employee->id,
            'content' => 'Laptop cua toi khong khoi dong tu sang nay.',
        ]);

        TicketComment::firstOrCreate([
            'ticket_id' => $ticket1->id,
            'user_id' => $technician->id,
            'content' => 'Toi se kiem tra nguon va pin cua laptop.',
        ]);

        TicketComment::firstOrCreate([
            'ticket_id' => $ticket2->id,
            'user_id' => $technician->id,
            'content' => 'Dang kiem tra loi phan mem.',
        ]);

        TicketComment::firstOrCreate([
            'ticket_id' => $ticket3->id,
            'user_id' => $technician->id,
            'content' => 'Da ho tro khoi phuc tai khoan.',
        ]);
    }
}
