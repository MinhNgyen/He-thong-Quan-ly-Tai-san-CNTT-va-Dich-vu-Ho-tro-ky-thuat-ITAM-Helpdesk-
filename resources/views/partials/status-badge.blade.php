@php
    $classes = [
        'open' => 'bg-sky-50 text-sky-700 border-sky-200',
        'assigned' => 'bg-violet-50 text-violet-700 border-violet-200',
        'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
        'waiting_user' => 'bg-orange-50 text-orange-700 border-orange-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $classes[$status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
    {{ \App\Enums\TicketStatus::tryFrom($status)?->label() ?? $status }}
</span>
