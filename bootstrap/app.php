<?php

use App\Http\Middleware\ApiTokenAuth;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'auth.token' => ApiTokenAuth::class,
        ]);

        // BM12: tiêu đề bảo mật cho mọi phản hồi.
        $middleware->append(SecurityHeaders::class);

        // Sau reverse proxy của nền tảng (Render, Railway...) mới nhận ra HTTPS; bật bằng TRUST_PROXIES=true.
        if (env('TRUST_PROXIES', false)) {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $wantsJson($request));

        // Quy ước lỗi thống nhất cho API: { success: false, message, errors? }.
        $exceptions->render(function (ValidationException $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            $defaults = [
                401 => 'Chưa xác thực hoặc token không hợp lệ.',
                403 => 'Bạn không có quyền thực hiện thao tác này.',
                404 => 'Không tìm thấy tài nguyên.',
                405 => 'Phương thức không được hỗ trợ.',
                409 => 'Xung đột dữ liệu.',
                419 => 'Phiên làm việc đã hết hạn.',
                429 => 'Bạn gửi yêu cầu quá nhanh, vui lòng thử lại sau.',
            ];

            // Không để lộ chi tiết nội bộ (tên model, đường dẫn) ở các lỗi 404/405.
            $message = in_array($status, [404, 405], true) || trim($e->getMessage()) === ''
                ? ($defaults[$status] ?? 'Yêu cầu không hợp lệ.')
                : $e->getMessage();

            return response()->json(['success' => false, 'message' => $message], $status, $e->getHeaders());
        });

        // BM9: lỗi 500 không lộ chi tiết khi APP_DEBUG=false.
        $exceptions->render(function (\Throwable $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request) || config('app.debug')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json(['success' => false, 'message' => 'Lỗi máy chủ, vui lòng thử lại sau.'], 500);
        });
    })->create();
