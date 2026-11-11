<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quyền truy cập (xem routes/web.php): Admin/Technician xem danh sách; thêm, sửa, xóa chỉ Admin.
 */
class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets)
    {
    }

    public function index(Request $request): View
    {
        $assets = $this->assets->list(
            $request->string('q')->toString(),
            $request->string('status')->toString(),
        );

        return view('assets.index', ['assets' => $assets]);
    }

    public function create(): View
    {
        return view('assets.form', [
            'asset' => new Asset(),
            'categories' => $this->assets->categories(),
            'parents' => $this->assets->parentCandidates(),
            'editing' => false,
        ]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $this->assets->create($request->validated());

        return redirect()->route('assets.index')->with('success', 'Đã thêm tài sản.');
    }

    public function edit(Asset $asset): View
    {
        return view('assets.form', [
            'asset' => $asset,
            'categories' => $this->assets->categories(),
            'parents' => $this->assets->parentCandidates($asset),
            'editing' => true,
        ]);
    }

    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->assets->update($asset, $request->validated());

        return redirect()->route('assets.index')->with('success', 'Đã cập nhật tài sản.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        if (! $this->assets->delete($asset)) {
            return back()->withErrors([
                'asset' => 'Không thể xóa tài sản đã có lịch sử bàn giao, bảo trì hoặc ticket. Hãy chuyển trạng thái sang Ngừng sử dụng.',
            ]);
        }

        return redirect()->route('assets.index')->with('success', 'Đã xóa tài sản.');
    }
}
