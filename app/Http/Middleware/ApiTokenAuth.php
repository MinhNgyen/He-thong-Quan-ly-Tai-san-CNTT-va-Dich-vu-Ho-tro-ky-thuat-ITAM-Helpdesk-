<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Xác thực API bằng Bearer token (stateless, không cần session/CSRF). */
class ApiTokenAuth
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        $user = $plain ? $this->auth->authenticateToken($plain) : null;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn.',
            ], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
