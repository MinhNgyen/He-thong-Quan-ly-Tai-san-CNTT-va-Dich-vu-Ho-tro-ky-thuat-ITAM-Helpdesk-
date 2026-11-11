<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'employee_code',
    'name',
    'email',
    'phone',
    'role_id',
    'department_id',
    'is_active',
    'password'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Tài khoản đang bị khóa tạm thời do đăng nhập sai nhiều lần (BM10). */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === RoleName::Admin->value;
    }

    public function isTechnician(): bool
    {
        return $this->role?->name === RoleName::Technician->value;
    }

    /** Admin hoặc Technician: nhân viên IT xử lý phiếu/tài sản. */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isTechnician();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
