@extends('layouts.app')

@section('title', 'Bảng công việc - ITAM Helpdesk')
@section('page_title', 'Bảng công việc của tôi')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl p-6 text-white bg-gradient-to-r from-brand-700 to-brand-500">
        <p class="text-brand-100 text-sm">Xin chào, {{ Auth::user()->name }}</p>
        <h2 class="text-xl font-bold mt-1">
            @if ($dashboard['mine_count'] > 0)
                Bạn đang phụ trách {{ $dashboard['mine_count'] }} phiếu cần xử lý
            @else
                Bạn chưa có phiếu nào đang xử lý
            @endif
        </h2>
        <p class="text-brand-100 text-sm mt-1">
            @if ($dashboard['unassigned_count'] > 0)
                Còn {{ $dashboard['unassigned_count'] }} phiếu mới chưa ai nhận, bạn có thể nhận việc ngay bên dưới.
            @else
                Hiện không có phiếu mới nào đang chờ nhận.
            @endif
        </p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        @foreach ([
            ['Đang xử lý của tôi', $dashboard['mine_count'], 'fa-screwdriver-wrench', 'bg-amber-50 text-amber-600'],
            ['Phiếu chờ nhận', $dashboard['unassigned_count'], 'fa-inbox', 'bg-sky-50 text-sky-600'],
            ['Tôi đã giải quyết', $dashboard['resolved_by_me'], 'fa-circle-check', 'bg-emerald-50 text-emerald-600'],
        ] as [$label, $value, $icon, $tone])
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">{{ $label }}</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">{{ $value }}</p>
                </div>
                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl {{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Việc của tôi --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800"><i class="fa-solid fa-screwdriver-wrench text-brand-500 mr-2"></i>Việc của tôi</h3>
                <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Tất cả phiếu</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($dashboard['mine'] as $ticket)
                    <li>
                        <a href="{{ route('tickets.show', $ticket) }}" class="block p-4 px-5 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-mono font-bold text-slate-500">{{ $ticket->ticket_code }}</p>
                                @include('partials.status-badge', ['status' => $ticket->status])
                            </div>
                            <p class="font-semibold text-slate-800 mt-1">{{ $ticket->title }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $ticket->createdBy->name ?? 'N/A' }} · cập nhật {{ $ticket->updated_at?->diffForHumans() }} · @include('partials.priority-badge', ['priority' => $ticket->priority])</p>
                        </a>
                    </li>
                @empty
                    <li class="p-10 text-center text-sm text-slate-400"><i class="fa-regular fa-circle-check text-2xl block mb-2"></i>Không có việc nào đang chờ bạn.</li>
                @endforelse
            </ul>
        </div>

        {{-- Phiếu chờ nhận --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800"><i class="fa-solid fa-inbox text-sky-500 mr-2"></i>Phiếu mới chờ nhận</h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($dashboard['unassigned'] as $ticket)
                    <li class="p-4 px-5 flex items-center justify-between gap-4">
                        <a href="{{ route('tickets.show', $ticket) }}" class="min-w-0 hover:opacity-80">
                            <p class="text-xs font-mono font-bold text-slate-500">{{ $ticket->ticket_code }}</p>
                            <p class="font-semibold text-slate-800 truncate">{{ $ticket->title }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $ticket->category->name ?? 'Chung' }} · @include('partials.priority-badge', ['priority' => $ticket->priority])</p>
                        </a>
                        <form method="POST" action="{{ route('tickets.claim', $ticket) }}" class="flex-shrink-0">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold">Nhận việc</button>
                        </form>
                    </li>
                @empty
                    <li class="p-10 text-center text-sm text-slate-400">Không có phiếu nào đang chờ nhận.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
