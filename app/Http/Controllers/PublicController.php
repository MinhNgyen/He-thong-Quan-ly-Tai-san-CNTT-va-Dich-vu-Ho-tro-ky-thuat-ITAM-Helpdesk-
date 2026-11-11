<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Services\SlaService;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Trang công khai (K10): không cần đăng nhập, có thẻ meta SEO, sitemap và robots. */
class PublicController extends Controller
{
    public function __construct(private readonly SlaService $sla)
    {
    }

    public function landing(): View
    {
        return view('public.landing');
    }

    public function sla(): View
    {
        $policies = $this->sla->policies()->map(fn ($p) => [
            'label' => TicketPriority::tryFrom($p->priority)?->label() ?? $p->priority,
            'name' => $p->name,
            'hours' => round($p->resolution_minutes / 60, 1),
        ]);

        return view('public.sla', [
            'policies' => $policies,
            'workHours' => sprintf('%02d:%02d - %02d:%02d', intdiv(config('sla.work_start_minutes'), 60), config('sla.work_start_minutes') % 60, intdiv(config('sla.work_end_minutes'), 60), config('sla.work_end_minutes') % 60),
        ]);
    }

    public function guide(): View
    {
        return view('public.guide');
    }

    public function sitemap(): Response
    {
        $urls = [url('/'), route('landing'), route('public.sla'), route('public.guide'), route('login'), route('register')];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>' . e($url) . '</loc><changefreq>monthly</changefreq></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api\nDisallow: /tickets\nDisallow: /assets\nDisallow: /reports\nDisallow: /notifications\n\nSitemap: " . route('sitemap') . "\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
