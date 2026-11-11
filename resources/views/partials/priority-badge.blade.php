@php
    $classes = [
        'low' => 'text-slate-500',
        'medium' => 'text-sky-600',
        'high' => 'text-orange-600 font-semibold',
        'urgent' => 'text-rose-600 font-bold',
    ];
@endphp
<span class="text-xs {{ $classes[$priority] ?? 'text-slate-500' }}">
    <i class="fa-solid fa-flag text-[10px] mr-1"></i>{{ \App\Enums\TicketPriority::tryFrom($priority)?->label() ?? $priority }}
</span>
