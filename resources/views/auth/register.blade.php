@extends('layouts.guest')

@section('title', 'Đăng ký - ITAM Helpdesk')

@section('content')
@php
    $input = 'w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500';
    $label = 'block text-sm font-medium text-slate-700 mb-1.5';
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Tạo tài khoản</h1>
    <p class="text-sm text-slate-500 mt-1">Tài khoản mới có vai trò Nhân viên. Quyền cao hơn do Quản trị viên cấp.</p>
</div>

@if ($errors->any())
    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-xl text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="employee_code" class="{{ $label }}">Mã nhân viên</label>
            <input id="employee_code" type="text" name="employee_code" value="{{ old('employee_code') }}" required placeholder="NV010" class="{{ $input }}">
        </div>
        <div>
            <label for="name" class="{{ $label }}">Họ và tên</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Nguyễn Văn A" class="{{ $input }}">
        </div>
    </div>

    <div>
        <label for="email" class="{{ $label }}">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="ten@congty.com" class="{{ $input }}">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="department_id" class="{{ $label }}">Phòng ban</label>
            <select id="department_id" name="department_id" required class="{{ $input }}">
                <option value="">-- Chọn phòng ban --</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="phone" class="{{ $label }}">Số điện thoại <span class="text-slate-400 font-normal">(tùy chọn)</span></label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="09xx xxx xxx" class="{{ $input }}">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="password" class="{{ $label }}">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Tối thiểu 8 ký tự" class="{{ $input }}">
        </div>
        <div>
            <label for="password_confirmation" class="{{ $label }}">Nhập lại mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" class="{{ $input }}">
        </div>
    </div>

    <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-brand-600/25 transition-colors mt-2">
        Đăng ký tài khoản
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    Đã có tài khoản?
    <a href="{{ route('login') }}" class="text-brand-600 hover:text-brand-700 font-semibold">Đăng nhập</a>
</p>
@endsection
