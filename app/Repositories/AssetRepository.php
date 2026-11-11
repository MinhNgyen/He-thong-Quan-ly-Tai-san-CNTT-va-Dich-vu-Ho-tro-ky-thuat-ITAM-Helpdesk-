<?php

namespace App\Repositories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssetRepository
{
    public function paginate(?string $term, ?string $status, int $perPage = 12): LengthAwarePaginator
    {
        $query = Asset::with(['category', 'parent']);

        if ($term !== null) {
            $query->where(fn ($q) => $q->where('asset_code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%"));
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function categories(): Collection
    {
        return AssetCategory::orderBy('name')->get();
    }

    /** Tài sản có thể gắn vào phiếu hỗ trợ. */
    public function assignable(): Collection
    {
        return Asset::whereIn('status', AssetStatus::assignable())->orderBy('asset_code')->get();
    }

    public const CACHE_STATUS_COUNTS = 'assets.status_counts';

    /**
     * Thống kê tài sản theo trạng thái (bộ nhớ đệm 60 giây, xóa khi tài sản thay đổi).
     *
     * @return array<string, int> mọi trạng thái đều có mặt (mặc định 0)
     */
    public function countByStatus(): array
    {
        return Cache::remember(self::CACHE_STATUS_COUNTS, 60, function () {
            $counts = Asset::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $result = [];
            foreach (AssetStatus::cases() as $status) {
                $result[$status->value] = (int) ($counts[$status->value] ?? 0);
            }

            return $result;
        });
    }

    public function forgetStatusCounts(): void
    {
        Cache::forget(self::CACHE_STATUS_COUNTS);
    }

    /**
     * Toàn bộ tài sản con cháu của một tài sản (nhiều cấp), kèm độ sâu.
     * Dùng CTE đệ quy (MySQL 8+, SQLite 3.8+); tham số được ràng buộc, không ghép chuỗi.
     *
     * @return list<object{id: int, parent_id: ?int, asset_code: string, name: string, status: string, depth: int}>
     */
    public function descendants(int $assetId): array
    {
        $rows = DB::select(
            'WITH RECURSIVE tree AS (
                SELECT id, parent_id, asset_code, name, status, 1 AS depth
                FROM assets WHERE parent_id = ?
                UNION ALL
                SELECT a.id, a.parent_id, a.asset_code, a.name, a.status, t.depth + 1
                FROM assets a INNER JOIN tree t ON a.parent_id = t.id
                WHERE t.depth < 50
            )
            SELECT id, parent_id, asset_code, name, status, depth FROM tree ORDER BY depth, asset_code',
            [$assetId],
        );

        return array_values($rows);
    }

    /** Mã các tài sản con cháu (dùng chặn tạo vòng lặp cha-con). @return list<int> */
    public function descendantIds(int $assetId): array
    {
        return array_map(fn ($row) => (int) $row->id, $this->descendants($assetId));
    }

    /** Tài sản có thể làm "cha": mọi tài sản trừ chính nó. */
    public function parentCandidates(?int $exceptId = null): Collection
    {
        return Asset::query()
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('asset_code')
            ->get(['id', 'asset_code', 'name']);
    }

    public function loadDetail(Asset $asset): Asset
    {
        return $asset->load([
            'category',
            'parent',
            'children',
            'assignments' => fn ($q) => $q->with(['user', 'assignedBy'])->latest('assigned_at'),
            'maintenances' => fn ($q) => $q->with('performedBy')->latest('maintenance_date'),
        ]);
    }

    /** Cập nhật trạng thái có điều kiện (nguyên tử): chỉ thành công nếu tài sản đang ở một trong các trạng thái $from. */
    public function transitionStatus(Asset $asset, array $from, string $to): bool
    {
        $affected = Asset::whereKey($asset->id)->whereIn('status', $from)->update(['status' => $to, 'updated_at' => now()]);
        $this->forgetStatusCounts();

        return $affected > 0;
    }

    public function count(): int
    {
        return Asset::count();
    }

    public function create(array $attributes): Asset
    {
        $this->forgetStatusCounts();

        return Asset::create($attributes);
    }

    public function update(Asset $asset, array $attributes): bool
    {
        $this->forgetStatusCounts();

        return $asset->update($attributes);
    }

    public function delete(Asset $asset): void
    {
        $this->forgetStatusCounts();
        $asset->delete();
    }

    /** Tài sản đã từng được bàn giao, bảo trì hoặc gắn vào phiếu thì không được xóa cứng. */
    public function hasHistory(Asset $asset): bool
    {
        return $asset->assignments()->exists()
            || $asset->maintenances()->exists()
            || $asset->tickets()->exists()
            || $asset->children()->exists();
    }
}
