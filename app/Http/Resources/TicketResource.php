<?php

namespace App\Http\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_label' => TicketPriority::tryFrom((string) $this->priority)?->label(),
            'status' => $this->status,
            'status_label' => TicketStatus::tryFrom((string) $this->status)?->label(),
            'category' => $this->whenLoaded('category', fn () => $this->category
                ? ['id' => $this->category->id, 'name' => $this->category->name]
                : null),
            'asset' => $this->whenLoaded('asset', fn () => $this->asset
                ? ['id' => $this->asset->id, 'asset_code' => $this->asset->asset_code, 'name' => $this->asset->name]
                : null),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy
                ? ['id' => $this->createdBy->id, 'name' => $this->createdBy->name]
                : null),
            'assigned_to' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo
                ? ['id' => $this->assignedTo->id, 'name' => $this->assignedTo->name]
                : null),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user' => $comment->user ? ['id' => $comment->user->id, 'name' => $comment->user->name] : null,
                'created_at' => $this->iso($comment->created_at),
            ])->values()),
            'history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($history) => [
                'action' => $history->action,
                'old_status' => $history->old_status,
                'new_status' => $history->new_status,
                'description' => $history->description,
                'user' => $history->user ? ['id' => $history->user->id, 'name' => $history->user->name] : null,
                'created_at' => $this->iso($history->created_at),
            ])->values()),
            'sla' => $this->slaPayload(),
            'resolved_at' => $this->iso($this->resolved_at),
            'closed_at' => $this->iso($this->closed_at),
            'created_at' => $this->iso($this->created_at),
            'updated_at' => $this->iso($this->updated_at),
        ];
    }

    /** Thông tin SLA: trạng thái, hạn xử lý và số phút làm việc còn lại. */
    private function slaPayload(): array
    {
        $state = app(\App\Services\SlaService::class)->state($this->resource);

        return [
            'state' => $state['state'],
            'due_at' => $this->iso($state['due_at']),
            'remaining_business_minutes' => $state['remaining_minutes'],
        ];
    }

    private function iso(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }
}
