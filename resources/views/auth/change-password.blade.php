@extends('layouts.app')

@section('title', 'Đổi mật khẩu - ITAM Helpdesk')
@section('page_title', 'Đổi mật khẩu')

@section('content')
<div class="max-w-md bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ route('password.change.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu hiện tại</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                   class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu mới</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="pw-help"
                   class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <p id="pw-help" class="text-xs text-slate-600 mt-1">Tối thiểu 8 ký tự, gồm cả chữ và số, khác mật khẩu hiện tại.</p>
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Nhập lại mật khẩu mới</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-xl">Đổi mật khẩu</button>
    </form>
</div>
@endsection
