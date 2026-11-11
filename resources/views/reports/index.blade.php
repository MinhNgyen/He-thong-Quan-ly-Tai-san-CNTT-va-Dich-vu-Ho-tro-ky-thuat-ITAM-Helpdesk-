@extends('layouts.app')

@section('title', 'Báo cáo - ITAM Helpdesk')
@section('page_title', 'Báo cáo và thống kê')

@section('content')
<div class="space-y-8">
    @foreach ($reports as $key => $report)
        @php
            $labelKey = array_key_first($report['columns']);
            $barKey = array_key_exists('total', $report['columns']) ? 'total' : (array_key_exists('assigned', $report['columns']) ? 'assigned' : null);
            $max = $barKey ? max(1, collect($report['rows'])->max(fn ($r) => (float) ($r[$barKey] ?? 0))) : 1;
        @endphp
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6" aria-labelledby="rep-{{ $key }}">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 id="rep-{{ $key }}" class="text-base font-bold text-slate-800">{{ $report['title'] }}</h2>
                <a href="{{ route('reports.export', $key) }}" class="inline-flex items-center space-x-2 bg-white border border-slate-300 hover:border-brand-400 text-slate-700 font-medium px-3 py-1.5 rounded-xl text-xs">
                    <i class="fa-solid fa-file-csv" aria-hidden="true"></i><span>Xuất CSV<span class="sr-only">: {{ $report['title'] }}</span></span>
                </a>
            </div>

            @if (count($report['rows']) === 0)
                <p class="text-sm text-slate-500">Chưa có dữ liệu cho báo cáo này.</p>
            @else
                @if ($barKey)
                    <div class="space-y-2 mb-5" role="img" aria-label="Biểu đồ cột: {{ $report['title'] }}">
                        @foreach ($report['rows'] as $row)
                            @php $value = (float) ($row[$barKey] ?? 0); @endphp
                            <div class="flex items-center space-x-3 text-xs">
                                <span class="w-36 truncate text-slate-700" title="{{ $row[$labelKey] }}">{{ $row[$labelKey] }}</span>
                                <span class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden"><span class="block h-3 bg-brand-600 rounded-full" style="width: {{ round($value / $max * 100) }}%"></span></span>
                                <span class="w-14 text-right font-semibold text-slate-700">{{ number_format($value, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">{{ $report['title'] }}</caption>
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr>@foreach ($report['columns'] as $label)<th scope="col" class="px-3 py-2">{{ $label }}</th>@endforeach</tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($report['rows'] as $row)
                                <tr>
                                    @foreach (array_keys($report['columns']) as $col)
                                        <td class="px-3 py-2">{{ is_numeric($row[$col] ?? null) && $col !== 'month' ? number_format((float) $row[$col], is_float($row[$col] + 0) ? 1 : 0, ',', '.') : ($row[$col] ?? '') }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
</div>
@endsection
