@extends('layouts.app')

@section('title', 'Tài sản ' . $asset->asset_code . ' - ITAM Helpdesk')
@section('page_title', 'Chi tiết tài sản')

@section('header_actions')
    <a href="{{ route('assets.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm">Quay lại danh sách</a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('assets.edit', $asset) }}" class="bg-white border border-slate-300 hover:border-brand-400 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm">Sửa</a>
    @endif
@endsection

@section('content')
@php
    $status = \App\Enums\AssetStatus::tryFrom($asset->status);
    $open = $asset->assignments->firstWhere('returned_at', null);
    $field = 'w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500';
@endphp
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm" aria-labelledby="asset-info">
            <h2 id="asset-info" class="text-lg font-bold text-slate-800">{{ $asset->name }}</h2>
            <p class="font-mono text-xs text-slate-600 mt-1">{{ $asset->asset_code }} · {{ $asset->category?->name }} · <strong>{{ $status?->label() ?? $asset->status }}</strong></p>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm mt-4">
                <div><dt class="text-slate-500 inline">Serial:</dt> <dd class="inline">{{ $asset->serial_number ?? '—' }}</dd></div>
                <div><dt class="text-slate-500 inline">Hãng / model:</dt> <dd class="inline">{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '—' }}</dd></div>
                <div><dt class="text-slate-500 inline">Ngày mua:</dt> <dd class="inline">{{ $asset->purchase_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500 inline">Giá mua:</dt> <dd class="inline">{{ $asset->purchase_price !== null ? number_format((float) $asset->purchase_price, 0, ',', '.') . ' đ' : '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500 inline">Thuộc tài sản:</dt> <dd class="inline">@if ($asset->parent)<a class="text-brand-700 hover:underline" href="{{ route('assets.show', $asset->parent) }}">{{ $asset->parent->asset_code }} - {{ $asset->parent->name }}</a>@else Tài sản gốc @endif</dd></div>
            </dl>
            @if ($asset->description)<p class="text-sm text-slate-700 mt-3">{{ $asset->description }}</p>@endif
        </section>

        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm" aria-labelledby="asset-tree">
            <h2 id="asset-tree" class="text-base font-bold text-slate-800 mb-3">Cây tài sản con ({{ count($descendants) }})</h2>
            @forelse ($descendants as $node)
                <p class="text-sm py-1" style="padding-left: {{ ($node->depth - 1) * 1.5 }}rem">
                    <span aria-hidden="true">└</span>
                    <a class="text-brand-700 hover:underline" href="{{ route('assets.show', $node->id) }}">{{ $node->asset_code }}</a>
                    <span class="text-slate-700">{{ $node->name }}</span>
                    <span class="text-xs text-slate-500">({{ \App\Enums\AssetStatus::tryFrom($node->status)?->label() ?? $node->status }})</span>
                </p>
            @empty
                <p class="text-sm text-slate-500">Tài sản này không có tài sản con.</p>
            @endforelse
        </section>

        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm" aria-labelledby="asset-assign">
            <h2 id="asset-assign" class="text-base font-bold text-slate-800 mb-3">Lịch sử bàn giao</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($asset->assignments as $a)
                    <li class="py-2">
                        <strong>{{ $a->user?->name }}</strong> · nhận {{ $a->assigned_at?->format('d/m/Y H:i') }}
                        @if ($a->returned_at) · trả {{ $a->returned_at->format('d/m/Y H:i') }} @else · <span class="text-emerald-700 font-semibold">đang sử dụng</span> @endif
                        <span class="block text-xs text-slate-500">Bàn giao bởi {{ $a->assignedBy?->name }}@if ($a->note) · {{ $a->note }}@endif</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Chưa có lịch sử bàn giao.</li>
                @endforelse
            </ul>
        </section>

        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm" aria-labelledby="asset-maint">
            <h2 id="asset-maint" class="text-base font-bold text-slate-800 mb-3">Lịch sử bảo trì</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($asset->maintenances as $m)
                    <li class="py-3">
                        <strong>{{ $m->title }}</strong> · {{ \Illuminate\Support\Carbon::parse($m->maintenance_date)->format('d/m/Y') }} · {{ ['pending' => 'Chờ', 'in_progress' => 'Đang bảo trì', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'][$m->status] ?? $m->status }}
                        · {{ number_format((float) $m->cost, 0, ',', '.') }} đ
                        @if (in_array($m->status, ['pending', 'in_progress']))
                            <form method="POST" action="{{ route('assets.maintenances.finish', $m->id) }}" class="flex flex-wrap items-center gap-2 mt-2">
                                @csrf @method('PATCH')
                                <label for="cost-{{ $m->id }}" class="sr-only">Chi phí thực tế</label>
                                <input id="cost-{{ $m->id }}" type="number" step="1000" min="0" name="cost" value="{{ (float) $m->cost }}" class="w-36 px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
                                <button name="outcome" value="completed" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold">Hoàn thành</button>
                                <button name="outcome" value="cancelled" class="px-3 py-1.5 bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold">Hủy</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Chưa có đợt bảo trì nào.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="space-y-6">
        @if (auth()->user()->isAdmin())
        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3" aria-labelledby="asset-handover">
            <h2 id="asset-handover" class="text-base font-bold text-slate-800">Bàn giao / thu hồi</h2>
            @if ($asset->status === 'available')
                <form method="POST" action="{{ route('assets.assign', $asset) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="user_id" class="block text-xs font-medium text-slate-700 mb-1">Người nhận</label>
                        <select id="user_id" name="user_id" required class="{{ $field }}">
                            @foreach ($recipients as $r)<option value="{{ $r->id }}">{{ $r->name }} ({{ $r->email }})</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="note" class="block text-xs font-medium text-slate-700 mb-1">Ghi chú</label>
                        <input id="note" name="note" maxlength="500" class="{{ $field }}">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-xl">Bàn giao</button>
                </form>
            @elseif ($open)
                <p class="text-sm text-slate-700">Đang được sử dụng bởi <strong>{{ $open->user?->name }}</strong>.</p>
                <form method="POST" action="{{ route('assets.return', $asset) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="note-return" class="block text-xs font-medium text-slate-700 mb-1">Ghi chú thu hồi</label>
                        <input id="note-return" name="note" maxlength="500" class="{{ $field }}">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm rounded-xl">Thu hồi tài sản</button>
                </form>
            @else
                <p class="text-sm text-slate-600">Chỉ bàn giao được tài sản ở trạng thái Sẵn sàng.</p>
            @endif
        </section>
        @endif

        <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3" aria-labelledby="asset-newmaint">
            <h2 id="asset-newmaint" class="text-base font-bold text-slate-800">Lập bảo trì</h2>
            @if ($asset->status === 'available')
                <form method="POST" action="{{ route('assets.maintenances.store', $asset) }}" class="space-y-3">
                    @csrf
                    <div><label for="m-title" class="block text-xs font-medium text-slate-700 mb-1">Nội dung bảo trì</label><input id="m-title" name="title" required maxlength="150" class="{{ $field }}"></div>
                    <div><label for="m-date" class="block text-xs font-medium text-slate-700 mb-1">Ngày thực hiện</label><input id="m-date" type="date" name="maintenance_date" required value="{{ now()->toDateString() }}" class="{{ $field }}"></div>
                    <div><label for="m-cost" class="block text-xs font-medium text-slate-700 mb-1">Chi phí dự kiến (đ)</label><input id="m-cost" type="number" name="cost" min="0" step="1000" class="{{ $field }}"></div>
                    <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-xl">Bắt đầu bảo trì</button>
                </form>
            @else
                <p class="text-sm text-slate-600">Chỉ lập bảo trì cho tài sản Sẵn sàng (thu hồi trước nếu đang được sử dụng).</p>
            @endif
        </section>
    </div>
</div>
@endsection
