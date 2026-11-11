<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Đăng nhập / đăng xuất API bằng Bearer token. */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->issueApiToken($request->validated(), 'api');

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at']->toIso8601String(),
                'user' => $this->userData($result['user']),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->userData($request->user()),
        ]);
    }

    /** Làm mới token: cấp token mới và thu hồi token cũ (luân chuyển). */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->auth->refreshApiToken((string) $request->bearerToken());

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn.'], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã làm mới token.',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at']->toIso8601String(),
                'user' => $this->userData($result['user']),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeToken((string) $request->bearerToken());

        return response()->json(['success' => true, 'message' => 'Đã đăng xuất.']);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
        ];
    }
}
