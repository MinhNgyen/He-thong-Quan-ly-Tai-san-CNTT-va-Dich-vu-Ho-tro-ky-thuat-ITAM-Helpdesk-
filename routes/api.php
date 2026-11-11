<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
| REST API (stateless): đăng nhập nhận Bearer token, gửi kèm header
|     Authorization: Bearer <token>
| Không dùng session/cookie nên không cần CSRF. Token hết hạn sau 7 ngày.
| Tài sản: Admin + Technician được xem; chỉ Admin được thêm/sửa/xóa.
| Phiếu: mọi người dùng đã đăng nhập, phạm vi dữ liệu và quyền theo TicketPolicy (giống giao diện web).
| Phiên bản: mọi điểm cuối nằm dưới tiền tố /api/v1 (quản lý phiên bản bằng đường dẫn).
| Giới hạn 60 request/phút mỗi token (bộ giới hạn api-token), đăng nhập 5 lần/phút/IP (bộ giới hạn login) cho mỗi người dùng đã đăng nhập.
*/
Route::prefix('v1')->group(function () {
    // Điểm cuối nội bộ cho trình lập lịch bên ngoài (không dùng phiên/Bearer; xác thực bằng khóa bí mật X-Cron-Key).
    Route::post('/internal/sla-escalate', [\App\Http\Controllers\Api\InternalController::class, 'escalate'])
        ->middleware('throttle:cron')->name('api.internal.sla');

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('api.login');

    Route::middleware(['auth.token', 'throttle:api-token'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');
        Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('api.refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

        Route::prefix('tickets')->name('api.tickets.')->controller(TicketController::class)->group(function () {
            Route::get('/options', 'options')->name('options');
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{ticket}', 'show')->whereNumber('ticket')->name('show');
            Route::put('/{ticket}', 'update')->whereNumber('ticket')->name('update');
            Route::patch('/{ticket}', 'update')->whereNumber('ticket')->name('update.patch');
            Route::post('/{ticket}/comments', 'comment')->whereNumber('ticket')->name('comment');
            Route::post('/{ticket}/claim', 'claim')->whereNumber('ticket')->name('claim');
        });

        Route::prefix('assets')->name('api.assets.')->controller(AssetController::class)->group(function () {
            Route::get('/', 'index')->middleware('role:Admin,Technician')->name('index');
            Route::get('/{asset}', 'show')->whereNumber('asset')->middleware('role:Admin,Technician')->name('show');
            Route::get('/{asset}/descendants', 'descendants')->whereNumber('asset')->middleware('role:Admin,Technician')->name('descendants');

            Route::middleware('role:Admin')->group(function () {
                Route::post('/', 'store')->name('store');
                Route::put('/{asset}', 'update')->whereNumber('asset')->name('update');
                Route::patch('/{asset}', 'update')->whereNumber('asset')->name('update.patch');
                Route::delete('/{asset}', 'destroy')->whereNumber('asset')->name('destroy');
            });
        });
    });
});
