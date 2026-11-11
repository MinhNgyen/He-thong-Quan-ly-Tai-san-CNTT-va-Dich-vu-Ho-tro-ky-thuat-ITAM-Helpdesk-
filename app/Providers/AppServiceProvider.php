<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Policies\TicketAttachmentPolicy;
use App\Policies\TicketPolicy;
use App\Services\NotificationService;
use App\Services\SlaService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SlaService::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        if (config("app.env") === "production" || env("TRUST_PROXIES")) {
            URL::forceScheme("https");
        }

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketAttachment::class, TicketAttachmentPolicy::class);

        View::composer("layouts.app", function ($view) {
            $view->with("unreadCount", Auth::check() ? app(NotificationService::class)->unreadCount(Auth::user()) : 0);
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for("login", fn (Request $request) => Limit::perMinute(5)->by("login|" . $request->ip()));
        RateLimiter::for("register", fn (Request $request) => Limit::perMinute(3)->by("register|" . $request->ip()));
        RateLimiter::for("forgot-password", fn (Request $request) => Limit::perMinute(3)->by("forgot|" . $request->ip()));
        RateLimiter::for("reset-password", fn (Request $request) => Limit::perMinute(5)->by("reset|" . $request->ip()));
        RateLimiter::for("change-password", fn (Request $request) => Limit::perMinute(5)->by("chgpw|" . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for("uploads", fn (Request $request) => Limit::perMinute(20)->by("upload|" . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for("cron", fn (Request $request) => Limit::perMinute(12)->by("cron|" . $request->ip()));
        RateLimiter::for("api-token", fn (Request $request) => Limit::perMinute(60)->by("api|" . ($request->bearerToken() ? hash("sha256", $request->bearerToken()) : $request->ip())));
    }
}
