<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tầng HTTP: nhận request, kiểm tra quyền, gọi TicketService và trả về view / redirect.
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function index(Request $request): View
    {
        $tickets = $this->tickets->list($request->user(), [
            'status' => $request->string('status')->toString(),
            'q' => $request->string('q')->toString(),
        ]);

        return view('tickets.index', ['tickets' => $tickets]);
    }

    public function create(): View
    {
        return view('tickets.create', $this->tickets->createFormOptions());
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create($request->user(), $request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Đã tạo phiếu hỗ trợ.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $user = $request->user();
        $canAssign = $user->can('assign', $ticket);

        // Chỉ hiện các trạng thái hợp lệ kế tiếp; người không có quyền phân công không thể đưa phiếu về "Mới".
        $statusOptions = TicketStatus::from($ticket->status)->options();
        if (! $canAssign) {
            unset($statusOptions[TicketStatus::Open->value]);
        }

        return view('tickets.show', [
            'statusOptions' => $statusOptions,
            'canComment' => $user->can('comment', $ticket),
            'ticket' => $this->tickets->loadDetail($ticket),
            'technicians' => $canAssign ? $this->tickets->assignableTechnicians() : collect(),
            'canUpdate' => $user->can('update', $ticket),
            'canAssign' => $canAssign,
            'canClaim' => $user->can('claim', $ticket),
            'sla' => $this->tickets->slaState($ticket),
        ]);
    }

    public function comment(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->addComment($request->user(), $ticket, $request->validated('content'));

        return back()->with('success', 'Đã thêm bình luận.');
    }

    /** Technician tự nhận một phiếu chưa có người xử lý. */
    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('claim', $ticket);

        if (! $this->tickets->claim($request->user(), $ticket)) {
            return back()->withErrors(['claim' => 'Phiếu này đã có người nhận xử lý.']);
        }

        return back()->with('success', 'Bạn đã nhận xử lý phiếu này.');
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->update($request->user(), $ticket, $request->validated(), $request->canAssign());

        return back()->with('success', 'Đã cập nhật phiếu hỗ trợ.');
    }
}
