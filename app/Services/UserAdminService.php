<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/** Quản trị người dùng (K1, K2): tạo, đổi vai trò, khóa/mở khóa. Chỉ Admin gọi (middleware role:Admin). */
class UserAdminService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $tokens,
        private readonly AuditService $audit,
    ) {
    }

    public function list(?string $term, ?int $roleId): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return $this->users->paginate($term === '' ? null : $term, $roleId);
    }

    public function roles(): Collection
    {
        return $this->users->roles();
    }

    public function departments(): Collection
    {
        return $this->users->departments();
    }

    public function create(array $data): User
    {
        $user = $this->users->create([
            'employee_code' => $data['employee_code'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'department_id' => $data['department_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'password' => $data['password'],
        ]);

        $this->audit->log('user.created', null, $user, ['role_id' => $user->role_id]);

        return $user;
    }

    /**
     * Cập nhật thông tin / vai trò / trạng thái. Admin không được tự hạ quyền hoặc tự khóa chính mình
     * (tránh làm mất quyền quản trị cuối cùng).
     */
    public function update(User $actor, User $user, array $data): User
    {
        $roleChanged = (int) $data['role_id'] !== (int) $user->role_id;
        $active = (bool) ($data['is_active'] ?? false);

        if ($actor->is($user) && ($roleChanged || ! $active)) {
            throw ValidationException::withMessages(['role_id' => 'Bạn không thể tự đổi vai trò hoặc tự khóa tài khoản của mình.']);
        }

        $oldActive = (bool) $user->is_active;

        $this->users->update($user, [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'department_id' => $data['department_id'] ?? null,
            'is_active' => $active,
        ]);

        if (! empty($data['password'])) {
            $this->users->update($user, ['password' => $data['password']]);
            $this->audit->log('user.password_set_by_admin', $actor, $user);
        }

        if ($roleChanged) {
            $this->audit->log('user.role_changed', $actor, $user, ['role_id' => (int) $data['role_id']]);
        }

        if ($oldActive !== $active) {
            $this->audit->log($active ? 'user.unlocked' : 'user.locked', $actor, $user);

            if (! $active) {
                $this->tokens->deleteAllFor($user); // khóa tài khoản: thu hồi mọi token API ngay
            }
        }

        if ($active) {
            $this->users->resetFailedLogins($user);
        }

        return $user->refresh();
    }
}
