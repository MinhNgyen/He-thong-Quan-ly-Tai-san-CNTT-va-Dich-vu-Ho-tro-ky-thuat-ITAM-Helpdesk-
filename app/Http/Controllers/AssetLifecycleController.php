<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AssetLifecycleService;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chi tiết tài sản, cây tài sản con, bàn giao / thu hồi và bảo trì.
 * Quyền (routes/web.php): xem + bảo trì: Admin và Technician; bàn giao / thu hồi: chỉ Admin.
 */
class AssetLifecycleController extends Controller
{
    public function __construct(
        private readonly AssetService $assets,
        private readonly AssetLifecycleService $lifecycle,
        private readonly UserRepository $users,
    ) {
    }

    public function show(Asset $asset): View
    {
        return view('assets.show', [
            'asset' => $this->assets->detail($asset),
            'descendants' => $this->assets->descendants($asset),
            'recipients' => auth()->user()->isAdmin() ? $this->users->activeUsers() : collect(),
        ]);
    }

    public function assign(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $recipient = User::where('is_active', true)->findOrFail($data['user_id']);
        $this->lifecycle->assign($asset, $recipient, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Đã bàn giao tài sản cho ' . $recipient->name . '.');
    }

    public function giveBack(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $this->lifecycle->giveBack($asset, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Đã thu hồi tài sản.');
    }

    public function startMaintenance(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'maintenance_date' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ]);

        $this->lifecycle->scheduleMaintenance($asset, $request->user(), $data);

        return back()->with('success', 'Đã lập phiếu bảo trì, tài sản chuyển sang trạng thái Bảo trì.');
    }

    public function finishMaintenance(Request $request, Maintenance $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'outcome' => ['required', 'in:completed,cancelled'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ]);

        $this->lifecycle->finishMaintenance($maintenance, $request->user(), $data['outcome'], isset($data['cost']) ? (float) $data['cost'] : null);

        return back()->with('success', 'Đã cập nhật đợt bảo trì.');
    }
}
