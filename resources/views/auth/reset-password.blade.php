@extends('layouts.guest')

@section('title', 'Đặt lại mật khẩu - ITAM Helpdesk')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Đặt lại mật khẩu</h1>
    <p class="text-sm text-slate-600 mt-1">Mật khẩu mới tối thiểu 8 ký tự, gồm cả chữ và số.</p>
</div>

@include('partials.alerts')

<form method="POST" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu mới</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Nhập lại mật khẩu mới</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl text-sm">Đặt lại mật khẩu</button>
</form>
@endsection
