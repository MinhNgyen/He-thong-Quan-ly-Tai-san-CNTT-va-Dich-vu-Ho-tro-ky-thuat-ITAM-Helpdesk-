<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Token truy cập API (chỉ lưu hash SHA-256, không lưu token gốc). */
class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
