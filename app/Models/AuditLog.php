<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Nhật ký hệ thống (K8). Chỉ ghi thêm: mọi thao tác sửa hoặc xóa qua Eloquent đều bị chặn.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Nhật ký hệ thống chỉ được ghi thêm, không được sửa.'));
        static::deleting(fn () => throw new LogicException('Nhật ký hệ thống không được xóa.'));
    }

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
