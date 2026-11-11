@extends('layouts.app')

@section('title', $def['title'] . ' - ITAM Helpdesk')
@section('page_title', 'Danh mục: ' . $def['title'])

@section('header_actions')
    <a href="{{ route('admin.catalog.create', $type) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-medium px-4 py-2 rounded-xl text-sm">
        <i class="fa-solid fa-plus text-xs mr-1" aria-hidden="true"></i> Thêm mới
    </a>
@endsection

@section('content')
<nav class="flex flex-wrap gap-2 mb-5" aria-label="Chọn loại danh mục">
    @foreach (\App\Support\CatalogRegistry::all() as $key => $d)
        <a href="{{ route('admin.catalog.index', $key) }}" @if ($key === $type) aria-current="page" @endif
           class="px-4 py-2 rounded-xl text-sm font-medium {{ $key === $type ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:border-brand-400' }}">{{ $d['title'] }}</a>
    @endforeach
</nav>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <caption class="sr-only">{{ $def['title'] }}</caption>
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th scope="col" class="px-4 py-3">Tên</th><th scope="col" class="px-4 py-3">Mô tả</th>
                @if (isset($def['boolean']))<th scope="col" class="px-4 py-3">Kích hoạt</th>@endif
                <th scope="col" class="px-4 py-3">Đang dùng</th><th scope="col" class="px-4 py-3"><span class="sr-only">Thao tác</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($items as $item)
                @php $count = $item->{$def['usage'] . '_count'}; @endphp
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $item->description }}</td>
                    @if (isset($def['boolean']))<td class="px-4 py-3">{{ $item->{$def['boolean']} ? 'Có' : 'Không' }}</td>@endif
                    <td class="px-4 py-3">{{ $count }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.catalog.edit', [$type, $item->id]) }}" class="text-brand-700 hover:underline mr-3">Sửa<span class="sr-only"> {{ $item->name }}</span></a>
                        @if ($count === 0)
                            <form method="POST" action="{{ route('admin.catalog.destroy', [$type, $item->id]) }}" class="inline" onsubmit="return confirm('Xóa mục này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-rose-700 hover:underline">Xóa<span class="sr-only"> {{ $item->name }}</span></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Chưa có dữ liệu.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
