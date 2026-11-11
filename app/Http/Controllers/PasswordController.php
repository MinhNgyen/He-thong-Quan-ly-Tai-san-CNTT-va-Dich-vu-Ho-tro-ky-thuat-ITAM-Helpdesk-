<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Đổi mật khẩu (đang đăng nhập) và khôi phục mật khẩu qua thư điện tử (K1). */
class PasswordController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function showChange(): View
    {
        return view('auth.change-password');
    }

    public function change(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $this->auth->changePassword($request->user(), $data['current_password'], $data['password']);

        return back()->with('success', 'Đã đổi mật khẩu.');
    }

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $this->auth->sendPasswordResetLink($data['email']);

        // Cùng một thông báo cho mọi email để không lộ tài khoản nào tồn tại (chống dò tài khoản).
        return back()->with('success', 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $this->auth->resetPassword($data + ['password_confirmation' => $request->input('password_confirmation')]);

        return redirect()->route('login')->with('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
    }
}
