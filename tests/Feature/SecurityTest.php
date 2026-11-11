<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Bằng chứng tự động cho BM7, BM10, BM12 và quy ước lỗi API thống nhất. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_and_api(): void
    {
        foreach (['/login', '/api/v1/me'] as $uri) {
            $response = $this->get($uri);

            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'DENY');
            $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
            $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        }
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
    }

    public function test_account_is_locked_after_five_failed_logins_even_with_correct_password(): void
    {
        $user = User::factory()->create(['email' => 'lock@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
                ->post('/login', ['email' => 'lock@example.com', 'password' => 'sai-mat-khau']);
        }

        $this->assertTrue($user->fresh()->isLocked());

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
            ->post('/login', ['email' => 'lock@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.account_locked', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_blocked', 'user_id' => $user->id]);
    }

    public function test_lock_expires_and_successful_login_resets_counter(): void
    {
        $user = User::factory()->create(['email' => 'unlock@example.com']);
        $user->forceFill(['locked_until' => now()->subMinute(), 'failed_login_attempts' => 3])->save();

        $this->post('/login', ['email' => 'unlock@example.com', 'password' => 'password'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
        $this->assertNull($user->fresh()->locked_until);
    }

    public function test_api_errors_use_a_uniform_json_shape(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message']);

        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'])
            ->assertJsonStructure(['errors' => ['email']]);

        $employee = User::factory()->create();
        $token = $this->postJson('/api/v1/login', ['email' => $employee->email, 'password' => 'password'])->json('data.token');

        $this->withToken($token)->getJson('/api/v1/assets')
            ->assertForbidden()
            ->assertJson(['success' => false]);

        $this->withToken($token)->getJson('/api/v1/tickets/999999')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Không tìm thấy tài nguyên.']);
    }

    public function test_old_unversioned_api_path_no_longer_exists(): void
    {
        $this->postJson('/api/login', [])->assertNotFound();
    }

    public function test_refresh_token_rotates_and_revokes_the_old_token(): void
    {
        $user = User::factory()->create();
        $old = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');

        $new = $this->withToken($old)->postJson('/api/v1/refresh-token')->assertOk()->json('data.token');

        $this->assertNotSame($old, $new);
        $this->withToken($old)->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken($new)->getJson('/api/v1/me')->assertOk();
    }

    public function test_locked_account_cannot_get_api_token(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['locked_until' => now()->addMinutes(10)])->save();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(422);
    }

    public function test_authenticated_api_calls_do_not_consume_login_attempts(): void
    {
        // Hồi quy: trước đây throttle không đặt tên dùng chung một khóa theo IP, nên gọi API đã xác thực làm cạn lượt đăng nhập.
        $user = User::factory()->create();
        $token = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');

        for ($i = 0; $i < 8; $i++) {
            $this->withToken($token)->getJson('/api/v1/me')->assertOk();
        }

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    }

    public function test_login_is_rate_limited_to_five_per_minute_per_ip(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        }

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429)
            ->assertJson(['success' => false]);
    }

    public function test_api_has_its_own_per_token_limit_of_60_per_minute(): void
    {
        $user = User::factory()->create();
        $token = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk()->assertHeader('X-RateLimit-Limit', '60');
    }
}
