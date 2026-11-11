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
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Má»™t thá»ƒ hiá»‡n cho má»—i request Ä‘á»ƒ ngÃ y nghá»‰ chá»‰ Ä‘Æ°á»£c náº¡p má»™t láº§n (trÃ¡nh N+1 khi liá»‡t kÃª nhiá»u phiáº¿u).
        $this->app->scoped(SlaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this$this->configureRateLimiting();
        if (config('app.env') === 'production' || env('TRUST_PROXIES')) {
            URL::forceScheme('https');
        }

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketAttachment::class, TicketAttachmentPolicy::class);

        // Sá»‘ thÃ´ng bÃ¡o chÆ°a Ä‘á»c hiá»ƒn thá»‹ trÃªn thanh Ä‘iá»u hÆ°á»›ng (K9).
        View::composer('layouts.app', function ($view) {
            $view->with('unreadCount', Auth::check() ? app(NotificationService::class)->unreadCount(Auth::user()) : 0);
        });
    }

    /**
     * Giá»›i háº¡n táº§n suáº¥t (BM10). Má»—i loáº¡i cÃ³ bá»™ Ä‘áº¿m RIÃŠNG (khÃ³a khÃ¡c nhau): vá»›i `throttle:N,M` khÃ´ng Ä‘áº·t tÃªn, má»i route dÃ¹ng chung
     * má»™t khÃ³a theo IP nÃªn cÃ¡c lÆ°á»£t gá»i API Ä‘Ã£ xÃ¡c thá»±c lÃ m cáº¡n lÆ°á»£t Ä‘Äƒng nháº­p cá»§a cÃ¹ng Ä‘á»‹a chá»‰.
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
        // API Ä‘Ã£ xÃ¡c thá»±c: 60 yÃªu cáº§u/phÃºt cho má»—i token (khÃ´ng dÃ¹ng chung vá»›i Ä‘Äƒng nháº­p)
        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(60)->by('api|' . ($request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip())));
    }
}

