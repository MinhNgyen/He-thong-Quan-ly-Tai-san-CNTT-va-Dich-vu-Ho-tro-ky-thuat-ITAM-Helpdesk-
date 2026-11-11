<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** REST API quản lý tài sản. Dùng lại tầng service/repository như giao diện web. */
class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = $this->assets->list(
            $request->query('q'),
            $request->query('status'),
        );

        return AssetResource::collection($assets)->additional([
            'success' => true,
            'message' => 'Lấy danh sách tài sản thành công.',
        ]);
    }

    public function show(Asset $asset): AssetResource
    {
        return (new AssetResource($asset->load('category')))->additional([
            'success' => true,
            'message' => 'Lấy thông tin tài sản thành công.',
        ]);
    }

    /** Toàn bộ tài sản con cháu (nhiều cấp) của một tài sản, kèm độ sâu - dùng truy vấn đệ quy. */
    public function descendants(Asset $asset): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy cây tài sản con thành công.',
            'data' => array_map(fn ($row) => [
                'id' => (int) $row->id,
                'parent_id' => $row->parent_id === null ? null : (int) $row->parent_id,
                'asset_code' => $row->asset_code,
                'name' => $row->name,
                'status' => $row->status,
                'depth' => (int) $row->depth,
            ], $this->assets->descendants($asset)),
        ]);
    }

    public function store(AssetRequest $request): JsonResponse
    {
        $asset = $this->assets->create($request->validated());

        return (new AssetResource($asset->load('category')))
            ->additional(['success' => true, 'message' => 'Tạo tài sản thành công.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(AssetRequest $request, Asset $asset): AssetResource
    {
        $this->assets->update($asset, $request->validated());

        return (new AssetResource($asset->refresh()->load('category')))->additional([
            'success' => true,
            'message' => 'Cập nhật tài sản thành công.',
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        if (! $this->assets->delete($asset)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài sản đã có lịch sử bàn giao, bảo trì hoặc phiếu hỗ trợ. Hãy chuyển trạng thái sang "Ngừng sử dụng".',
            ], 409);
        }

        return response()->json(['success' => true, 'message' => 'Xóa tài sản thành công.']);
    }
}
