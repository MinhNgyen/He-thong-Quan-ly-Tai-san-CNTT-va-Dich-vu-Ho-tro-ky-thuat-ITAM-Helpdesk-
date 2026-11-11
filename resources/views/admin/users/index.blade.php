@extends('layouts.app')

@section('title', 'Người dùng - ITAM Helpdesk')
@section('page_title', 'Quản lý người dùng')

@section('header_actions')
    <a href="{{ route('admin.users.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-medium px-4 py-2 rounded-xl text-sm">
        <i class="fa-solid fa-user-plus text-xs mr-1" aria-hidden="true"></i> Thêm người dùng
    </a>
@endsection

@section('content')
<form method="GET" class="flex flex-wrap gap-3 mb-5" role="search">
    <label for="q" class="sr-only">Tìm theo tên, email hoặc mã nhân viên</label>
    <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên, email, mã nhân viên"
           class="flex-1 min-w-[220px] px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
    <label for="role" class="sr-only">Lọc theo vai trò</label>
    <select id="role" name="role" class="px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
        <option value="">Tất cả vai trò</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected(request('role') == $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-xl">Lọc</button>
</form>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <caption class="sr-only">Danh sách người dùng</caption>
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th scope="col" class="px-4 py-3">Mã NV</th><th scope="col" class="px-4 py-3">Họ tên</th><th scope="col" class="px-4 py-3">Email</th>
                <th scope="col" class="px-4 py-3">Vai trò</th><th scope="col" class="px-4 py-3">Phòng ban</th><th scope="col" class="px-4 py-3">Trạng thái</th><th scope="col" class="px-4 py-3"><span class="sr-only">Thao tác</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $u)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $u->employee_code }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                    <td class="px-4 py-3">{{ $u->email }}</td>
                    <td class="px-4 py-3">{{ $u->role?->name }}</td>
                    <td class="px-4 py-3">{{ $u->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if (! $u->is_active)
                            <span class="px-2 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-semibold">Đã khóa</span>
                        @elseif ($u->isLocked())
                            <span class="px-2 py-1 rounded-full bg-amber-50 text-amber-800 text-xs font-semibold">Khóa tạm thời</span>
                        @else
                            <span class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">Hoạt động</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.users.edit', $u) }}" class="text-brand-700 hover:underline">Sửa<span class="sr-only"> {{ $u->name }}</span></a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
