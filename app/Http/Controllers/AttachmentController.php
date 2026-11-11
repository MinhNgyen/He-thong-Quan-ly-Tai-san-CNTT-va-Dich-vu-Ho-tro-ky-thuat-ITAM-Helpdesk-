<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function store(StoreAttachmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->attachments->store($ticket, $request->user(), $request->file('file'));

        return back()->with('success', 'Đã đính kèm tệp.');
    }

    public function download(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return $this->attachments->download($attachment, $request->user());
    }

    public function destroy(Request $request, TicketAttachment $attachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);

        $this->attachments->delete($attachment, $request->user());

        return back()->with('success', 'Đã xóa tệp đính kèm.');
    }
}
