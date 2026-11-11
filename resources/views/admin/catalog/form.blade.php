@extends('layouts.app')

@section('title', $def['title'] . ' - ITAM Helpdesk')
@section('page_title', ($item ? 'Sửa' : 'Thêm') . ': ' . $def['title'])

@section('content')
@php $field = 'w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500'; @endphp
<div class="max-w-lg bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <form method="POST" action="{{ $item ? route('admin.catalog.update', [$type, $item->id]) : route('admin.catalog.store', $type) }}" class="space-y-4">
        @csrf
        @if ($item) @method('PUT') @endif
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Tên</label>
            <input id="name" name="name" value="{{ old('name', $item?->name) }}" required maxlength="100" class="{{ $field }}">
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Mô tả</label>
            <input id="description" name="description" value="{{ old('description', $item?->description) }}" maxlength="255" class="{{ $field }}">
        </div>
        @if (isset($def['boolean']))
            <div class="flex items-center space-x-2">
                <input type="hidden" name="{{ $def['boolean'] }}" value="0">
                <input id="flag" type="checkbox" name="{{ $def['boolean'] }}" value="1" @checked(old($def['boolean'], $item?->{$def['boolean']} ?? true)) class="w-4 h-4">
                <label for="flag" class="text-sm text-slate-700">Đang kích hoạt</label>
            </div>
        @endif
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-xl">Lưu</button>
            <a href="{{ route('admin.catalog.index', $type) }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm rounded-xl">Hủy</a>
        </div>
    </form>
</div>
@endsection
