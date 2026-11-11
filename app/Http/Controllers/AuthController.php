<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->auth->login($request->validated());

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function showRegister(): View
    {
        return view('auth.register', ['departments' => $this->auth->departments()]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->auth->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Đăng ký tài khoản thành công!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->recordLogout($request->user());
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
