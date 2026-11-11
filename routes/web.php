<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetLifecycleController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// Trang công khai (K10): không cần đăng nhập. "/" hiển thị giới thiệu cho khách, bảng điều khiển cho người đã đăng nhập.
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::controller(PublicController::class)->group(function () {
    Route::get('/gioi-thieu', 'landing')->name('landing');
    Route::get('/cam-ket-sla', 'sla')->name('public.sla');
    Route::get('/huong-dan', 'guide')->name('public.guide');
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('/robots.txt', 'robots')->name('robots');
});

// Khách (chưa đăng nhập)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.store');

    // Khôi phục mật khẩu
    Route::get('/forgot-password', [PasswordController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendLink'])->middleware('throttle:forgot-password')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:reset-password')->name('password.update');
});

// Người dùng đã đăng nhập
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Đổi mật khẩu
    Route::get('/account/password', [PasswordController::class, 'showChange'])->name('password.change');
    Route::post('/account/password', [PasswordController::class, 'change'])->middleware('throttle:change-password')->name('password.change.store');

    // Thông báo (K9): chỉ thao tác trên thông báo của chính mình
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Phiếu hỗ trợ: quyền theo từng phiếu do TicketPolicy quyết định (xem TicketController / FormRequest).
    Route::prefix('tickets')->name('tickets.')->controller(TicketController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticket}', 'show')->whereNumber('ticket')->name('show');
        Route::patch('/{ticket}', 'update')->whereNumber('ticket')->name('update');
        Route::post('/{ticket}/comments', 'comment')->whereNumber('ticket')->name('comments.store');
        Route::post('/{ticket}/claim', 'claim')->whereNumber('ticket')->name('claim');
        Route::post('/{ticket}/attachments', [AttachmentController::class, 'store'])->middleware('throttle:uploads')->whereNumber('ticket')->name('attachments.store');
    });

    // Tệp đính kèm (K6): tải xuống và xóa đi qua TicketAttachmentPolicy (không có đường dẫn tĩnh)
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->whereNumber('attachment')->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->whereNumber('attachment')->name('attachments.destroy');

    // Tài sản: Admin và Technician được xem; chỉ Admin được thêm, sửa, xóa, bàn giao.
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/', [AssetController::class, 'index'])->middleware('role:Admin,Technician')->name('index');

        Route::middleware('role:Admin,Technician')->group(function () {
            Route::get('/{asset}', [AssetLifecycleController::class, 'show'])->whereNumber('asset')->name('show');
            Route::post('/{asset}/maintenances', [AssetLifecycleController::class, 'startMaintenance'])->whereNumber('asset')->name('maintenances.store');
            Route::patch('/maintenances/{maintenance}', [AssetLifecycleController::class, 'finishMaintenance'])->whereNumber('maintenance')->name('maintenances.finish');
        });

        Route::middleware('role:Admin')->group(function () {
            Route::get('/create', [AssetController::class, 'create'])->name('create');
            Route::post('/', [AssetController::class, 'store'])->name('store');
            Route::get('/{asset}/edit', [AssetController::class, 'edit'])->whereNumber('asset')->name('edit');
            Route::put('/{asset}', [AssetController::class, 'update'])->whereNumber('asset')->name('update');
            Route::delete('/{asset}', [AssetController::class, 'destroy'])->whereNumber('asset')->name('destroy');
            Route::post('/{asset}/assign', [AssetLifecycleController::class, 'assign'])->whereNumber('asset')->name('assign');
            Route::post('/{asset}/return', [AssetLifecycleController::class, 'giveBack'])->whereNumber('asset')->name('return');
        });
    });

    // Quản trị (chỉ Admin): người dùng, danh mục, nhật ký hệ thống, báo cáo
    Route::middleware('role:Admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

            Route::prefix('catalog/{type}')->name('catalog.')->controller(CatalogController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
                Route::put('/{id}', 'update')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
            });
        });

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}/export.csv', [ReportController::class, 'export'])->name('reports.export');
    });
});
