@extends('layouts.app')

@section('title', 'Quản lý Tài sản - ITAM')
@section('page_title', 'Danh mục Tài sản IT')

@section('header_actions')
    @if(Auth::user()->isAdmin())
        <a href="{{ route('assets.create') }}" class="inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-medium px-4 py-2 rounded-xl text-sm transition-all shadow-sm shadow-brand-200">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Thêm Tài sản mới</span>
        </a>
    @endif
@endsection

@section('content')
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-xs">
                <tr>
                    <th class="py-3.5 px-6">Mã Tài sản</th>
                    <th class="py-3.5 px-6">Tên thiết bị</th>
                    <th class="py-3.5 px-6">Trạng thái</th>
                    <th class="py-3.5 px-6">Vị trí</th>
                    @if(Auth::user()->isAdmin())
                        <th class="py-3.5 px-6 text-right">Thao tác Admin</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assets as $asset)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-4 px-6 font-mono text-xs font-bold text-slate-600">
                        {{ $asset->asset_code ?? $asset->asset_tag }}
                    </td>
                    <td class="py-4 px-6 font-semibold text-slate-800">
                        <a href="{{ route('assets.show', $asset) }}" class="text-brand-700 hover:underline">{{ $asset->name }}</a>
                        <span class="block text-xs text-slate-500 font-normal">S/N: {{ $asset->serial_number ?? 'N/A' }}@if ($asset->parent) · thuộc {{ $asset->parent->asset_code }}@endif</span>
                    </td>
                    <td class="py-4 px-6">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ \App\Enums\AssetStatus::tryFrom($asset->status)?->label() ?? $asset->status }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-slate-600">
                        {{ $asset->location ?? 'Kho IT' }}
                    </td>

                    @if(Auth::user()->isAdmin())
                        <td class="py-4 px-6 text-right space-x-2">
                            <a href="{{ route('assets.edit', $asset) }}" class="text-slate-400 hover:text-brand-600 p-1" title="Chỉnh sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-slate-400">
                        Chưa có tài sản nào trong hệ thống.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection