<?php

namespace App\Http\Controllers;

use App\Services\AssetService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Trang chủ: mỗi vai trò có một giao diện tổng quan riêng. */
class HomeController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly AssetService $assets,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        // Khách chưa đăng nhập thấy trang giới thiệu công khai (K10).
        if (! $user) {
            return view('public.landing');
        }

        $dashboard = $this->tickets->dashboard($user);

        if ($user->isAdmin()) {
            return view('dashboard.admin', [
                'dashboard' => $dashboard,
                'assetStats' => $this->assets->statusCounts(),
            ]);
        }

        if ($user->isTechnician()) {
            return view('dashboard.technician', ['dashboard' => $dashboard]);
        }

        return view('dashboard.employee', ['dashboard' => $dashboard]);
    }
}
