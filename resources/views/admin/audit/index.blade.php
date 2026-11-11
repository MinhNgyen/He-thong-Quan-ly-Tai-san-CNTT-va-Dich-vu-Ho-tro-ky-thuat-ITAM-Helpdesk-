@extends('layouts.app')

@section('title', 'Nhật ký hệ thống - ITAM Helpdesk')
@section('page_title', 'Nhật ký hệ thống')

@section('content')
<form method="GET" class="grid sm:grid-cols-5 gap-3 mb-5" role="search">
    <div>
        <label for="action" class="block text-xs font-medium text-slate-700 mb-1">Hành động</label>
        <select id="action" name="action" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
            <option value="">Tất cả</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" @selected(($filters['action'] ?? '') === $a)>{{ $a }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="q" class="block text-xs font-medium text-slate-700 mb-1">Người dùng / IP</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
    </div>
    <div>
        <label for="from" class="block text-xs font-medium text-slate-700 mb-1">Từ ngày</label>
        <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
    </div>
    <div>
        <label for="to" class="block text-xs font-medium text-slate-700 mb-1">Đến ngày</label>
        <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm">
    </div>
    <div class="flex items-end"><button type="submit" class="w-full px-5 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-xl">Lọc</button></div>
</form>

<p class="text-xs text-slate-600 mb-3">Nhật ký chỉ ghi thêm: hệ thống không có chức năng sửa hay xóa bản ghi.</p>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <caption class="sr-only">Nhật ký hệ thống</caption>
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th scope="col" class="px-4 py-3">Thời điểm</th><th scope="col" class="px-4 py-3">Người thực hiện</th><th scope="col" class="px-4 py-3">Hành động</th><th scope="col" class="px-4 py-3">Đối tượng</th><th scope="col" class="px-4 py-3">IP</th><th scope="col" class="px-4 py-3">Chi tiết</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3">{{ $log->user?->name ?? 'Hệ thống / khách' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $log->action }}</td>
                    <td class="px-4 py-3">{{ $log->entity_type ? $log->entity_type . ' #' . $log->entity_id : '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">{{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Không có bản ghi phù hợp.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
