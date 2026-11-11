@extends('layouts.app')

@section('title', 'Thông báo - ITAM Helpdesk')
@section('page_title', 'Thông báo')

@section('header_actions')
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="bg-white border border-slate-300 hover:border-brand-400 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm">Đánh dấu tất cả đã đọc</button>
    </form>
@endsection

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">
    @forelse ($notifications as $n)
        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
            @csrf
            <button type="submit" class="w-full text-left px-5 py-4 flex items-start space-x-3 hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/50' }}">
                <span class="mt-1.5 w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $n->read_at ? 'bg-slate-300' : 'bg-brand-600' }}" aria-hidden="true"></span>
                <span class="flex-1">
                    <span class="block text-sm font-semibold text-slate-800">{{ $n->data['title'] ?? 'Thông báo' }}@unless ($n->read_at)<span class="sr-only"> (chưa đọc)</span>@endunless</span>
                    <span class="block text-sm text-slate-600">{{ $n->data['body'] ?? '' }}</span>
                    <span class="block text-xs text-slate-500 mt-1">{{ $n->created_at->format('d/m/Y H:i') }}</span>
                </span>
            </button>
        </form>
    @empty
        <p class="p-8 text-center text-sm text-slate-500">Bạn chưa có thông báo nào.</p>
    @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
