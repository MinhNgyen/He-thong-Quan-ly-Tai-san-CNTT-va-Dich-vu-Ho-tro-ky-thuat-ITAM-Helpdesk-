<?php

namespace App\Services;

use App\Models\Asset;
use App\Repositories\AssetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssetService
{
    public function __construct(
        private readonly AssetRepository $assets,
        private readonly AuditService $audit,
    ) {
    }

    public function list(?string $term, ?string $status): LengthAwarePaginator
    {
        return $this->assets->paginate($this->blankToNull($term), $this->blankToNull($status));
    }

    public function categories(): Collection
    {
        return $this->assets->categories();
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        return $this->assets->countByStatus();
    }

    public function count(): int
    {
        return $this->assets->count();
    }

    public function create(array $data): Asset
    {
        $asset = $this->assets->create($this->normalize($data));
        $this->audit->log('asset.created', null, $asset, ['code' => $asset->asset_code]);

        return $asset;
    }

    public function update(Asset $asset, array $data): void
    {
        $this->assets->update($asset, $this->normalize($data));
        $this->audit->log('asset.updated', null, $asset, ['code' => $asset->asset_code]);
    }

    /** Dữ liệu cho form: danh mục và danh sách tài sản có thể làm cha. */
    public function parentCandidates(?Asset $asset = null): Collection
    {
        return $this->assets->parentCandidates($asset?->id);
    }

    /** Chọn tài sản $parentId làm cha của $asset có tạo vòng lặp không (chính nó hoặc con cháu của nó)? */
    public function wouldCreateCycle(Asset $asset, int $parentId): bool
    {
        return $parentId === $asset->id || in_array($parentId, $this->assets->descendantIds($asset->id), true);
    }

    /** @return list<object> */
    public function descendants(Asset $asset): array
    {
        return $this->assets->descendants($asset->id);
    }

    public function detail(Asset $asset): Asset
    {
        return $this->assets->loadDetail($asset);
    }

    /**
     * Xóa tài sản. Trả về false (không xóa) nếu tài sản đã có lịch sử bàn giao, bảo trì hoặc phiếu hỗ trợ.
     */
    public function delete(Asset $asset): bool
    {
        if ($this->assets->hasHistory($asset)) {
            return false;
        }

        $this->assets->delete($asset);
        $this->audit->log('asset.deleted', null, null, ['id' => $asset->id, 'code' => $asset->asset_code]);

        return true;
    }

    private function normalize(array $data): array
    {
        $data['asset_code'] = strtoupper(trim($data['asset_code']));
        $data['parent_id'] = ($data['parent_id'] ?? null) === '' ? null : ($data['parent_id'] ?? null);

        return $data;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
