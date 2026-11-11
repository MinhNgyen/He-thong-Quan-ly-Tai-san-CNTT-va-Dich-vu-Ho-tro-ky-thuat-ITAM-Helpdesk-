<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;

/** Truy cập dữ liệu cho bàn giao / thu hồi tài sản và bảo trì. */
class AssetLifecycleRepository
{
    public function openAssignment(Asset $asset): ?AssetAssignment
    {
        return AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_at')->latest('assigned_at')->first();
    }

    public function createAssignment(array $attributes): AssetAssignment
    {
        return AssetAssignment::create($attributes);
    }

    public function closeAssignment(AssetAssignment $assignment, ?string $note = null): void
    {
        $assignment->update([
            'returned_at' => now(),
            'note' => $note !== null ? trim(($assignment->note ? $assignment->note . ' | ' : '') . $note) : $assignment->note,
        ]);
    }

    public function createMaintenance(array $attributes): Maintenance
    {
        return Maintenance::create($attributes);
    }

    public function updateMaintenance(Maintenance $maintenance, array $attributes): bool
    {
        return $maintenance->update($attributes);
    }

    /** Còn đợt bảo trì nào đang chạy (chưa hoàn thành/hủy) trên tài sản không? */
    public function hasActiveMaintenance(Asset $asset, ?int $exceptId = null): bool
    {
        return Maintenance::where('asset_id', $asset->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();
    }

    /** @return Collection<int, Maintenance> bảo trì sắp đến ngày thực hiện */
    public function upcoming(int $days = 14): Collection
    {
        return Maintenance::with('asset')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereBetween('maintenance_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('maintenance_date')
            ->get();
    }
}
