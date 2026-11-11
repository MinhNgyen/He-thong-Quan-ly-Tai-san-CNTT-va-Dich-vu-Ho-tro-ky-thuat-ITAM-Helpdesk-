<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Vui lòng đăng nhập.');
        }

        if (! $user->is_active) {
            abort(403, 'Tài khoản đã bị vô hiệu hóa.');
        }

        $roleName = $user->role?->name;

        if (! $roleName || ! in_array($roleName, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
