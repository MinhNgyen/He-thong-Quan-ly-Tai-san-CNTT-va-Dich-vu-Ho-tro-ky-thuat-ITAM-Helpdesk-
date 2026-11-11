<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ticket_code', 'created_by', 'assigned_to', 'category_id', 'asset_id', 'title', 'description', 'priority', 'status', 'resolved_at', 'closed_at', 'sla_due_at', 'sla_paused_at', 'sla_remaining_minutes', 'sla_escalated_at'])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
        ];
    }

    /**
     * Phạm vi dữ liệu theo vai trò (dùng chung cho danh sách và dashboard):
     * - Admin: tất cả
     * - Technician: phiếu được gán cho mình, phiếu chưa ai nhận, phiếu do mình tạo
     * - Employee: chỉ phiếu do mình tạo
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isTechnician()) {
            return $query->where(fn (Builder $q) => $q
                ->where('assigned_to', $user->id)
                ->orWhereNull('assigned_to')
                ->orWhere('created_by', $user->id));
        }

        return $query->where('created_by', $user->id);
    }

    /** Tìm theo mã phiếu hoặc tiêu đề. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('ticket_code', 'like', "%{$term}%")
            ->orWhere('title', 'like', "%{$term}%"));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class);
    }
}
