<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** K1: đổi mật khẩu và khôi phục mật khẩu qua thư điện tử. */
class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/account/password', [
            'current_password' => 'password', 'password' => 'MoiMoi12345', 'password_confirmation' => 'MoiMoi12345',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('MoiMoi12345', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_changed', 'user_id' => $user->id]);
    }

    public function test_change_password_rejects_wrong_current_weak_or_same_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/account/password', ['current_password' => 'sai', 'password' => 'MoiMoi12345', 'password_confirmation' => 'MoiMoi12345'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($user)->post('/account/password', ['current_password' => 'password', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'])
            ->assertSessionHasErrors('password');
        $this->actingAs($user)->post('/account/password', ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->post('/account/password', ['current_password' => 'x', 'password' => 'y'])->assertRedirect('/login');
    }

    public function test_forgot_password_sends_link_and_does_not_reveal_whether_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'co@example.com']);

        $known = $this->post('/forgot-password', ['email' => 'co@example.com']);
        $unknown = $this->post('/forgot-password', ['email' => 'khong@example.com']);

        $this->assertSame(session('success'), $known->getSession()->get('success'));
        $unknown->assertSessionHas('success', $known->getSession()->get('success'));
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_reset_password_with_valid_token_revokes_api_tokens(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $apiToken = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'reset@example.com', 'password' => 'DatLai12345', 'password_confirmation' => 'DatLai12345',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('DatLai12345', $user->fresh()->password));
        $this->withToken($apiToken)->getJson('/api/v1/me')->assertUnauthorized();

        // token chỉ dùng được một lần
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'reset@example.com', 'password' => 'KhacNua12345', 'password_confirmation' => 'KhacNua12345',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create(['email' => 'x@example.com']);

        $this->post('/reset-password', [
            'token' => 'khong-hop-le', 'email' => 'x@example.com', 'password' => 'DatLai12345', 'password_confirmation' => 'DatLai12345',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_unlocks_a_locked_account(): void
    {
        $user = User::factory()->create(['email' => 'l@example.com']);
        $user->forceFill(['locked_until' => now()->addMinutes(10), 'failed_login_attempts' => 2])->save();

        $this->post('/reset-password', [
            'token' => Password::createToken($user), 'email' => 'l@example.com', 'password' => 'DatLai12345', 'password_confirmation' => 'DatLai12345',
        ])->assertRedirect('/login');

        $this->assertFalse($user->fresh()->isLocked());
    }
}
