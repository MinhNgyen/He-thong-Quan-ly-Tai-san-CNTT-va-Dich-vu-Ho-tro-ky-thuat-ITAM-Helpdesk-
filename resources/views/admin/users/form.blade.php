@extends('layouts.app')

@section('title', ($editing ? 'Sửa' : 'Thêm') . ' người dùng - ITAM Helpdesk')
@section('page_title', $editing ? 'Sửa người dùng' : 'Thêm người dùng')

@section('content')
@php
    $field = 'w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500';
@endphp
<div class="max-w-2xl bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ $editing ? route('admin.users.update', $editUser) : route('admin.users.store') }}" class="grid sm:grid-cols-2 gap-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        @unless ($editing)
        <div>
            <label for="employee_code" class="block text-sm font-medium text-slate-700 mb-1">Mã nhân viên</label>
            <input id="employee_code" name="employee_code" value="{{ old('employee_code') }}" required class="{{ $field }}">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="{{ $field }}">
        </div>
        @else
        <div class="sm:col-span-2 text-sm text-slate-600">Mã nhân viên <strong>{{ $editUser->employee_code }}</strong> · Email <strong>{{ $editUser->email }}</strong> (không thay đổi được)</div>
        @endunless

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Họ tên</label>
            <input id="name" name="name" value="{{ old('name', $editUser->name) }}" required class="{{ $field }}">
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Điện thoại</label>
            <input id="phone" name="phone" value="{{ old('phone', $editUser->phone) }}" class="{{ $field }}">
        </div>
        <div>
            <label for="role_id" class="block text-sm font-medium text-slate-700 mb-1">Vai trò</label>
            <select id="role_id" name="role_id" required class="{{ $field }}">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $editUser->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700 mb-1">Phòng ban</label>
            <select id="department_id" name="department_id" class="{{ $field }}">
                <option value="">— Không —</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}" @selected(old('department_id', $editUser->department_id) == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">{{ $editing ? 'Đặt lại mật khẩu (bỏ trống nếu giữ nguyên)' : 'Mật khẩu' }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" @required(! $editing) aria-describedby="pw-help" class="{{ $field }}">
            <p id="pw-help" class="text-xs text-slate-600 mt-1">Tối thiểu 8 ký tự, gồm cả chữ và số.</p>
        </div>
        <div class="sm:col-span-2 flex items-center space-x-2">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editUser->is_active)) class="w-4 h-4">
            <label for="is_active" class="text-sm text-slate-700">Tài khoản đang hoạt động (bỏ chọn để khóa)</label>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-xl">Lưu</button>
            <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm rounded-xl">Hủy</a>
        </div>
    </form>
</div>
@endsection
