@extends('layouts.guest')

@section('title', 'Quên mật khẩu - ITAM Helpdesk')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Quên mật khẩu</h1>
    <p class="text-sm text-slate-600 mt-1">Nhập email tài khoản, hệ thống sẽ gửi liên kết đặt lại mật khẩu (có hiệu lực 60 phút).</p>
</div>

@include('partials.alerts')

<form method="POST" action="{{ route('password.email') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl text-sm">Gửi liên kết đặt lại</button>
    <p class="text-center text-sm"><a href="{{ route('login') }}" class="text-brand-700 hover:underline">Quay lại đăng nhập</a></p>
</form>
@endsection
