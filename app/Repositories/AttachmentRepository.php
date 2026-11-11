<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\TicketAttachment;

class AttachmentRepository
{
    public function countFor(Ticket $ticket): int
    {
        return TicketAttachment::where('ticket_id', $ticket->id)->count();
    }

    public function create(array $attributes): TicketAttachment
    {
        return TicketAttachment::create($attributes);
    }

    public function delete(TicketAttachment $attachment): void
    {
        $attachment->delete();
    }
}
