<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Repositories\AssetLifecycleRepository;
use App\Repositories\AssetRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Vòng đời tài sản: cấp phát (bàn giao) - thu hồi, và lập/hoàn thành bảo trì.
 * Mọi chuyển trạng thái dùng cập nhật có điều kiện trong giao dịch nên hai thao tác đồng thời
 * không thể cùng bàn giao một tài sản cho hai người.
 */
class AssetLifecycleService
{
    public function __construct(
        private readonly AssetLifecycleRepository $lifecycle,
        private readonly AssetRepository $assets,
        private readonly AuditService $audit,
    ) {
    }

    /** Bàn giao tài sản đang "Sẵn sàng" cho một người dùng. */
    public function assign(Asset $asset, User $recipient, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($asset, $recipient, $actor, $note) {
            if (! $this->assets->transitionStatus($asset, [AssetStatus::Available->value], AssetStatus::InUse->value)) {
                throw ValidationException::withMessages(['asset' => 'Chỉ bàn giao được tài sản đang ở trạng thái Sẵn sàng.']);
            }

            $this->lifecycle->createAssignment([
                'asset_id' => $asset->id,
                'user_id' => $recipient->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'note' => $note,
            ]);

            $this->audit->log('asset.assigned', $actor, $asset, ['to' => $recipient->id]);
        });
    }

    /** Thu hồi tài sản đang được sử dụng về trạng thái "Sẵn sàng". */
    public function giveBack(Asset $asset, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($asset, $actor, $note) {
            $open = $this->lifecycle->openAssignment($asset);

            if (! $open || ! $this->assets->transitionStatus($asset, [AssetStatus::InUse->value], AssetStatus::Available->value)) {
                throw ValidationException::withMessages(['asset' => 'Tài sản này hiện không có người đang sử dụng.']);
            }

            $this->lifecycle->closeAssignment($open, $note);
            $this->audit->log('asset.returned', $actor, $asset, ['from' => $open->user_id]);
        });
    }

    /**
     * Lập phiếu bảo trì. Tài sản đang được bàn giao phải thu hồi trước; khi lập, tài sản chuyển sang "Bảo trì".
     *
     * @param array{title: string, description?: ?string, maintenance_date: string, cost?: float|int|string|null} $data
     */
    public function scheduleMaintenance(Asset $asset, User $actor, array $data): Maintenance
    {
        return DB::transaction(function () use ($asset, $actor, $data) {
            if (! $this->assets->transitionStatus($asset, [AssetStatus::Available->value], AssetStatus::Maintenance->value)) {
                throw ValidationException::withMessages(['asset' => 'Chỉ lập bảo trì cho tài sản đang Sẵn sàng (hãy thu hồi nếu tài sản đang được sử dụng).']);
            }

            $maintenance = $this->lifecycle->createMaintenance([
                'asset_id' => $asset->id,
                'performed_by' => $actor->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'maintenance_date' => $data['maintenance_date'],
                'cost' => $data['cost'] ?? 0,
                'status' => 'in_progress',
            ]);

            $this->audit->log('asset.maintenance_started', $actor, $asset, ['maintenance_id' => $maintenance->id]);

            return $maintenance;
        });
    }

    /** Hoàn thành hoặc hủy đợt bảo trì; tài sản quay lại "Sẵn sàng" khi không còn đợt bảo trì nào khác. */
    public function finishMaintenance(Maintenance $maintenance, User $actor, string $outcome, ?float $cost = null): void
    {
        if (! in_array($outcome, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Trạng thái bảo trì không hợp lệ.']);
        }

        DB::transaction(function () use ($maintenance, $actor, $outcome, $cost) {
            if (! in_array($maintenance->status, ['pending', 'in_progress'], true)) {
                throw ValidationException::withMessages(['status' => 'Đợt bảo trì này đã kết thúc.']);
            }

            $this->lifecycle->updateMaintenance($maintenance, [
                'status' => $outcome,
                'completed_at' => $outcome === 'completed' ? now()->toDateString() : null,
                'cost' => $cost ?? $maintenance->cost,
                'performed_by' => $actor->id,
            ]);

            $asset = $maintenance->asset;

            if (! $this->lifecycle->hasActiveMaintenance($asset, $maintenance->id)) {
                $this->assets->transitionStatus($asset, [AssetStatus::Maintenance->value], AssetStatus::Available->value);
            }

            $this->audit->log('asset.maintenance_' . $outcome, $actor, $asset, ['maintenance_id' => $maintenance->id]);
        });
    }
}
