<?php

namespace App\Repositories;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Carbon;

class ApiTokenRepository
{
    public function create(User $user, string $name, string $hash, ?Carbon $expiresAt): ApiToken
    {
        return ApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => $hash,
            'expires_at' => $expiresAt,
        ]);
    }

    /** Token còn hạn theo hash, kèm người dùng và vai trò. */
    public function findValid(string $hash): ?ApiToken
    {
        return ApiToken::with('user.role')
            ->where('token', $hash)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    public function touch(ApiToken $token): void
    {
        $token->forceFill(['last_used_at' => now()])->save();
    }

    public function deleteByHash(string $hash): void
    {
        ApiToken::where('token', $hash)->delete();
    }

    /** Thu hồi mọi token của một người dùng (sau khi đặt lại mật khẩu hoặc khóa tài khoản). */
    public function deleteAllFor(User $user): void
    {
        ApiToken::where('user_id', $user->id)->delete();
    }
}
