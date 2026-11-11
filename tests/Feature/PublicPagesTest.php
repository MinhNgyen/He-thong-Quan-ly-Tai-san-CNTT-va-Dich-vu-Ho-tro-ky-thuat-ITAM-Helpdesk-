<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** K10: trang công khai (không cần đăng nhập), thẻ meta SEO, sitemap/robots, khả năng truy cập cơ bản. */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_open_to_guests_with_unique_title_and_description(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $titles = [];
        $descriptions = [];

        foreach (['/', '/gioi-thieu', '/cam-ket-sla', '/huong-dan'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('#<html lang="vi">#', $html);
            $this->assertMatchesRegularExpression('#<title>[^<]{5,}</title>#', $html);
            $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{40,}">#', $html);
            $this->assertStringContainsString('<link rel="canonical"', $html);
            $this->assertStringContainsString('property="og:title"', $html);
            $this->assertStringContainsString('<main id="main"', $html);
            $this->assertSame(1, substr_count($html, '<h1'), "{$uri} phải có đúng một h1");
            $this->assertStringContainsString('href="#main"', $html); // liên kết bỏ qua điều hướng
            $this->assertStringNotContainsString('noindex', $html);

            preg_match('#<title>(.*?)</title>#', $html, $t);
            preg_match('#<meta name="description" content="(.*?)">#', $html, $d);
            $titles[$uri] = $t[1];
            $descriptions[$uri] = $d[1];
        }

        // '/' và '/gioi-thieu' cùng nội dung; các trang còn lại phải có tiêu đề và mô tả riêng biệt
        $this->assertCount(3, array_unique(array_values(array_diff_key($titles, ['/' => 1]))));
        $this->assertCount(3, array_unique(array_values(array_diff_key($descriptions, ['/' => 1]))));
    }

    public function test_sla_page_is_data_driven(): void
    {
        $this->seed(SlaPolicySeeder::class);

        $this->get('/cam-ket-sla')->assertOk()->assertSee('Khẩn cấp')->assertSee('08:00 - 17:00')->assertSee('thứ Sáu');
    }

    public function test_footer_shows_simulation_notice_on_every_layout(): void
    {
        $this->get('/login')->assertSee('Dữ liệu mô phỏng phục vụ mục đích học tập');
        $this->get('/gioi-thieu')->assertSee('Dữ liệu mô phỏng phục vụ mục đích học tập');
        $this->actingAs(User::factory()->create())->get('/')->assertSee('Dữ liệu mô phỏng phục vụ mục đích học tập');
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertSee('Dữ liệu mô phỏng phục vụ mục đích học tập');
    }

    public function test_sitemap_and_robots(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $sitemap->headers->get('Content-Type'));
        $this->assertStringContainsString('/cam-ket-sla', $sitemap->getContent());
        $this->assertNotFalse(simplexml_load_string($sitemap->getContent()), 'sitemap phải là XML hợp lệ');

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap:', $robots);
    }

    public function test_private_pages_are_noindex_and_logged_in_user_sees_dashboard_at_root(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('noindex', $html);
        $this->assertStringNotContainsString('Một nơi cho mọi yêu cầu hỗ trợ CNTT', $html);
    }

    public function test_login_form_has_labels_for_inputs(): void
    {
        $html = $this->get('/login')->getContent();

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('for="password"', $html);
        $this->assertStringContainsString('Quên mật khẩu?', $html);
    }
}
