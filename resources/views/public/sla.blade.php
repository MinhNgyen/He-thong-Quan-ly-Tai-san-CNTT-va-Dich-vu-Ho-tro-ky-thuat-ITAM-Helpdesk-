@extends('layouts.public')

@section('title', 'Cam kết thời gian xử lý (SLA)')
@section('meta_description', 'Cam kết thời gian xử lý yêu cầu hỗ trợ CNTT theo mức ưu tiên, tính bằng giờ làm việc; đồng hồ tạm dừng khi chờ người dùng phản hồi.')

@section('content')
<h1 class="text-3xl font-bold text-slate-900">Cam kết thời gian xử lý (SLA)</h1>
<p class="mt-3 text-slate-700 max-w-3xl">Thời gian cam kết được tính bằng <strong>giờ làm việc</strong>: {{ $workHours }}, từ thứ Hai đến thứ Sáu, không tính ngày nghỉ. Yêu cầu gửi ngoài giờ sẽ bắt đầu được tính từ đầu giờ làm việc kế tiếp.</p>

<div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <caption class="sr-only">Bảng cam kết SLA theo mức ưu tiên</caption>
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th scope="col" class="px-4 py-3">Mức ưu tiên</th><th scope="col" class="px-4 py-3">Cam kết</th><th scope="col" class="px-4 py-3">Số giờ làm việc</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($policies as $p)
                <tr><th scope="row" class="px-4 py-3 text-left font-semibold">{{ $p['label'] }}</th><td class="px-4 py-3">{{ $p['name'] }}</td><td class="px-4 py-3">{{ $p['hours'] }} giờ</td></tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2 class="text-xl font-bold text-slate-900 mt-10">Quy tắc tính</h2>
<ul class="mt-3 list-disc list-inside space-y-2 text-slate-700">
    <li>Ví dụ: phiếu mức Cao (4 giờ làm việc) tạo lúc 17:00 thứ Sáu sẽ đến hạn lúc 12:00 thứ Hai.</li>
    <li>Khi phiếu ở trạng thái <em>Chờ người dùng phản hồi</em>, đồng hồ tạm dừng; khi bạn phản hồi, đồng hồ chạy tiếp với thời gian còn lại.</li>
    <li>Phiếu quá hạn được hệ thống tự động leo thang tới quản trị viên.</li>
</ul>
@endsection
