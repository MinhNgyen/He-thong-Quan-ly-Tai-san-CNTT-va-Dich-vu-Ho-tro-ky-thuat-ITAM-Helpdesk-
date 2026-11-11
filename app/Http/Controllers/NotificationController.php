<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $this->notifications->paginateFor($request->user())]);
    }

    /** Đánh dấu đã đọc rồi chuyển tới phiếu liên quan. Chỉ thao tác được trên thông báo của chính mình. */
    public function read(Request $request, string $id): RedirectResponse
    {
        $url = $this->notifications->markRead($request->user(), $id);

        abort_if($url === null, 404);

        return redirect($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }
}
