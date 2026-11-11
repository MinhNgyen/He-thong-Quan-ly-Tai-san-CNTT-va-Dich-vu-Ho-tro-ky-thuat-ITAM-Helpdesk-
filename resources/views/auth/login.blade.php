@extends('layouts.guest')

@section('title', 'Đăng nhập - ITAM Helpdesk')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Chào mừng trở lại</h1>
    <p class="text-sm text-slate-500 mt-1">Đăng nhập để tiếp tục sử dụng hệ thống.</p>
</div>

@if ($errors->any())
    <div class="mb-5 flex items-start space-x-2 bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-xl text-sm">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
        <div class="relative">
            <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="ten@congty.com"
                class="w-full pl-10 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
        </div>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu</label>
        <div class="relative">
            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                class="w-full pl-10 pr-11 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            <button type="button" id="toggle-password" aria-label="Hiện/ẩn mật khẩu"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-regular fa-eye text-sm"></i>
            </button>
        </div>
    </div>

    <div class="text-right -mt-2">
        <a href="{{ route('password.request') }}" class="text-sm text-brand-700 hover:underline">Quên mật khẩu?</a>
    </div>

    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-brand-600/25 transition-colors">
        Đăng nhập
    </button>
</form>

@if (app()->environment('local'))
    <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">
            <i class="fa-solid fa-flask mr-1"></i> Tài khoản demo (chỉ hiện ở môi trường local)
        </p>
        <div class="grid grid-cols-3 gap-2">
            <button type="button" data-demo="admin@example.com" class="demo-btn px-2 py-2.5 rounded-xl border border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-xs font-medium text-slate-700 transition-colors">
                <i class="fa-solid fa-user-shield block text-base mb-1 text-indigo-600"></i>Quản trị
            </button>
            <button type="button" data-demo="technician@example.com" class="demo-btn px-2 py-2.5 rounded-xl border border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-xs font-medium text-slate-700 transition-colors">
                <i class="fa-solid fa-screwdriver-wrench block text-base mb-1 text-teal-600"></i>Nhân viên IT
            </button>
            <button type="button" data-demo="employee@example.com" class="demo-btn px-2 py-2.5 rounded-xl border border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-xs font-medium text-slate-700 transition-colors">
                <i class="fa-solid fa-user block text-base mb-1 text-sky-600"></i>Nhân viên
            </button>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Bấm để điền sẵn email và mật khẩu demo, sau đó bấm Đăng nhập.</p>
    </div>
@endif

<p class="mt-8 text-center text-sm text-slate-500">
    Chưa có tài khoản?
    <a href="{{ route('register') }}" class="text-brand-600 hover:text-brand-700 font-semibold">Đăng ký</a>
</p>

<script>
    document.getElementById('toggle-password').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'fa-regular fa-eye-slash text-sm' : 'fa-regular fa-eye text-sm';
    });
    document.querySelectorAll('.demo-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('email').value = btn.dataset.demo;
            document.getElementById('password').value = 'Password123!';
        });
    });
</script>
@endsection
