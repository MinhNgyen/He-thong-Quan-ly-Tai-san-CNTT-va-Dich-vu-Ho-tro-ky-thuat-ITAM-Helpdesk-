<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Policies\TicketAttachmentPolicy;
use App\Services\NotificationService;
use App\Services\SlaService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Một thể hiện cho mỗi request để ngày nghỉ chỉ được nạp một lần (tránh N+1 khi liệt kê nhiều phiếu).
        $this->app->scoped(SlaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketAttachment::class, TicketAttachmentPolicy::class);

        // Số thông báo chưa đọc hiển thị trên thanh điều hướng (K9).
        View::composer('layouts.app', function ($view) {
            $view->with('unreadCount', Auth::check() ? app(NotificationService::class)->unreadCount(Auth::user()) : 0);
        });
    }

    /**
     * Giới hạn tần suất (BM10). Mỗi loại có bộ đếm RIÊNG (khóa khác nhau): với `throttle:N,M` không đặt tên, mọi route dùng chung
     * một khóa theo IP nên các lượt gọi API đã xác thực làm cạn lượt đăng nhập của cùng địa chỉ.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by('login|' . $request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3)->by('register|' . $request->ip()));
        RateLimiter::for('forgot-password', fn (Request $request) => Limit::perMinute(3)->by('forgot|' . $request->ip()));
        RateLimiter::for('reset-password', fn (Request $request) => Limit::perMinute(5)->by('reset|' . $request->ip()));
        RateLimiter::for('change-password', fn (Request $request) => Limit::perMinute(5)->by('chgpw|' . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by('upload|' . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('cron', fn (Request $request) => Limit::perMinute(12)->by('cron|' . $request->ip()));
        // API đã xác thực: 60 yêu cầu/phút cho mỗi token (không dùng chung với đăng nhập)
        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(60)->by('api|' . ($request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip())));
    }
}
