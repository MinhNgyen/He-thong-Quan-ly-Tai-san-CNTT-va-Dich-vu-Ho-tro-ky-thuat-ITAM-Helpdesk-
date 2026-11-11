<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /** Thời hạn token API (ngày). */
    private const API_TOKEN_DAYS = 7;

    /** BM10: khóa tạm thời tài khoản sau số lần đăng nhập sai liên tiếp này, trong số phút này. */
    private const LOCK_THRESHOLD = 5;
    private const LOCK_MINUTES = 15;

    public function __construct(
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $tokens,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * Đăng nhập web bằng email/mật khẩu. Tài khoản bị vô hiệu hóa sẽ bị đăng xuất ngay.
     * Việc tạo lại session thuộc tầng HTTP (controller).
     *
     * @param array{email: string, password: string} $credentials
     *
     * @throws ValidationException
     */
    public function login(array $credentials): void
    {
        $user = $this->users->findByEmail($credentials['email']);

        $this->guardNotLocked($user);

        if (! Auth::attempt($credentials)) {
            $this->recordFailure($user, $credentials['email']);

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ]);
        }

        if (! Auth::user()->is_active) {
            $inactive = Auth::user();
            Auth::logout();
            $this->audit->log('auth.login_failed', $inactive, null, ['reason' => 'inactive']);

            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị vô hiệu hóa.',
            ]);
        }

        $this->users->resetFailedLogins(Auth::user());
        $this->audit->log('auth.login', Auth::user(), null, ['channel' => 'web']);
    }

    /** Ghi nhật ký đăng xuất (gọi trước khi hủy phiên). */
    public function recordLogout(?User $user): void
    {
        if ($user) {
            $this->audit->log('auth.logout', $user);
        }
    }

    /** Đăng ký tài khoản mới; vai trò luôn là Employee, do máy chủ gán chứ không lấy từ form. */
    public function register(array $data): User
    {
        $user = $this->users->create([
            'employee_code' => $data['employee_code'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'],
            'role_id' => $this->users->employeeRoleId(),
            'is_active' => true,
            'password' => $data['password'],
        ]);

        $this->audit->log('auth.registered', $user, $user);

        return $user;
    }

    /** Danh sách phòng ban cho form đăng ký. */
    public function departments(): Collection
    {
        return $this->users->departments();
    }

    /**
     * Đăng nhập API (không dùng session): kiểm tra thông tin rồi cấp token.
     *
     * @param array{email: string, password: string} $credentials
     * @return array{token: string, expires_at: \Illuminate\Support\Carbon, user: User}
     *
     * @throws ValidationException
     */
    public function issueApiToken(array $credentials, string $name = 'api'): array
    {
        $user = $this->users->findByEmail($credentials['email']);

        $this->guardNotLocked($user);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->recordFailure($user, $credentials['email'], 'api');

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ]);
        }

        if (! $user->is_active) {
            $this->audit->log('auth.login_failed', $user, null, ['reason' => 'inactive', 'channel' => 'api']);

            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị vô hiệu hóa.',
            ]);
        }

        $this->users->resetFailedLogins($user);

        $result = $this->createToken($user, $name);
        $this->audit->log('auth.login', $user, null, ['channel' => 'api']);

        return $result;
    }

    /**
     * Làm mới token API: cấp token mới và thu hồi token cũ (luân chuyển token).
     *
     * @return array{token: string, expires_at: \Illuminate\Support\Carbon, user: User}|null null nếu token không hợp lệ
     */
    public function refreshApiToken(string $plain): ?array
    {
        $user = $this->authenticateToken($plain);

        if (! $user) {
            return null;
        }

        $this->tokens->deleteByHash($this->hash($plain));
        $this->audit->log('auth.token_refreshed', $user);

        return $this->createToken($user, 'api');
    }

    /** @return array{token: string, expires_at: \Illuminate\Support\Carbon, user: User} */
    private function createToken(User $user, string $name): array
    {
        $plain = Str::random(48);
        $token = $this->tokens->create($user, $name, $this->hash($plain), now()->addDays(self::API_TOKEN_DAYS));

        return ['token' => $plain, 'expires_at' => $token->expires_at, 'user' => $user];
    }

    /** @throws ValidationException khi tài khoản đang bị khóa tạm thời */
    private function guardNotLocked(?User $user): void
    {
        if ($user && $user->isLocked()) {
            $this->audit->log('auth.login_blocked', $user, null, ['locked_until' => $user->locked_until->toIso8601String()]);

            throw ValidationException::withMessages([
                'email' => 'Tài khoản tạm thời bị khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau ' . self::LOCK_MINUTES . ' phút.',
            ]);
        }
    }

    private function recordFailure(?User $user, string $email, string $channel = 'web'): void
    {
        if ($user && $this->users->registerFailedLogin($user, self::LOCK_THRESHOLD, self::LOCK_MINUTES)) {
            $this->audit->log('auth.account_locked', $user, null, ['channel' => $channel]);
        }

        $this->audit->log('auth.login_failed', $user, null, ['email' => $email, 'channel' => $channel]);
    }

    /** Đổi mật khẩu cho người đang đăng nhập; phải nhập đúng mật khẩu hiện tại. */
    public function changePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        $this->users->update($user, ['password' => $new]);
        $this->audit->log('auth.password_changed', $user, $user);
    }

    /** Gửi liên kết đặt lại mật khẩu (thư điện tử). Luôn trả về như nhau để không lộ email nào tồn tại. */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
        $this->audit->log('auth.password_reset_requested', null, null, ['email' => $email]);
    }

    /** @throws ValidationException khi token không hợp lệ hoặc đã hết hạn */
    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            ['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $data['password_confirmation'], 'token' => $data['token']],
            function (User $user, string $password) {
                $this->users->update($user, ['password' => $password]);
                $this->users->resetFailedLogins($user);
                $this->tokens->deleteAllFor($user);
                $this->audit->log('auth.password_reset', $user, $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }
    }

    /** Trả về người dùng sở hữu token hợp lệ (còn hạn, tài khoản đang hoạt động), hoặc null. */
    public function authenticateToken(string $plain): ?User
    {
        $token = $this->tokens->findValid($this->hash($plain));

        if (! $token || ! $token->user || ! $token->user->is_active) {
            return null;
        }

        $this->tokens->touch($token);

        return $token->user;
    }

    public function revokeToken(string $plain): void
    {
        $this->tokens->deleteByHash($this->hash($plain));
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
