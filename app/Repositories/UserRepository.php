<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /** Technician đang hoạt động: chỉ nhóm này được phân công xử lý phiếu (Admin chỉ điều phối). */
    public function assignableTechnicians(): Collection
    {
        return User::whereHas('role', fn ($q) => $q->where('name', RoleName::Technician->value))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function isTechnician(int $userId): bool
    {
        return User::whereKey($userId)
            ->whereHas('role', fn ($q) => $q->where('name', RoleName::Technician->value))
            ->exists();
    }

    public function employeeRoleId(): int
    {
        return Role::where('name', RoleName::Employee->value)->firstOrFail()->id;
    }

    public function findByEmail(string $email): ?User
    {
        return User::with('role')->where('email', $email)->first();
    }

    public function departments(): Collection
    {
        return Department::orderBy('name')->get();
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    /** Quản trị viên đang hoạt động (nhận thông báo leo thang SLA). */
    public function admins(): Collection
    {
        return User::whereHas('role', fn ($q) => $q->where('name', RoleName::Admin->value))
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function roles(): Collection
    {
        return Role::orderBy('id')->get();
    }

    /** Danh sách người dùng cho màn hình quản trị: tìm theo tên, email, mã nhân viên; lọc theo vai trò. */
    public function paginate(?string $term, ?int $roleId, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['role', 'department']);

        if ($term !== null) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('employee_code', 'like', "%{$term}%"));
        }

        if ($roleId !== null) {
            $query->where('role_id', $roleId);
        }

        return $query->orderBy('id')->paginate($perPage)->withQueryString();
    }

    public function update(User $user, array $attributes): bool
    {
        return $user->update($attributes);
    }

    /** Người dùng đang hoạt động (chọn người nhận bàn giao tài sản). */
    public function activeUsers(): Collection
    {
        return User::where('is_active', true)->orderBy('name')->get();
    }

    public function findById(int $id): ?User
    {
        return User::with('role')->find($id);
    }

    public function registerFailedLogin(User $user, int $threshold, int $lockMinutes): bool
    {
        $attempts = $user->failed_login_attempts + 1;
        $locked = $attempts >= $threshold;

        $user->forceFill([
            'failed_login_attempts' => $locked ? 0 : $attempts,
            'locked_until' => $locked ? now()->addMinutes($lockMinutes) : $user->locked_until,
        ])->save();

        return $locked;
    }

    public function resetFailedLogins(User $user): void
    {
        if ($user->failed_login_attempts !== 0 || $user->locked_until !== null) {
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        }
    }
}
