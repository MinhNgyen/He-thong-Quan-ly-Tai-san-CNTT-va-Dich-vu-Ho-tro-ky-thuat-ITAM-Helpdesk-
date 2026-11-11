@extends('layouts.public')

@section('title', 'Quản lý tài sản CNTT và hỗ trợ kỹ thuật')
@section('meta_description', 'Gửi yêu cầu hỗ trợ CNTT, theo dõi tiến độ xử lý theo cam kết SLA tính theo giờ làm việc và quản lý tài sản IT trong một hệ thống.')

@section('content')
<section aria-labelledby="hero-title" class="rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-900 text-white p-8 sm:p-12">
    <h1 id="hero-title" class="text-3xl sm:text-4xl font-bold leading-tight">Một nơi cho mọi yêu cầu hỗ trợ CNTT và tài sản IT</h1>
    <p class="mt-4 max-w-2xl text-brand-50">Gửi yêu cầu, theo dõi tiến độ theo cam kết thời gian xử lý (SLA) tính bằng giờ làm việc, và quản lý vòng đời thiết bị: cấp phát, thu hồi, bảo trì.</p>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('login') }}" class="px-5 py-3 bg-white text-brand-800 font-semibold rounded-xl">Đăng nhập</a>
        <a href="{{ route('register') }}" class="px-5 py-3 border border-white/70 text-white font-semibold rounded-xl hover:bg-white/10">Đăng ký tài khoản</a>
    </div>
</section>

<section aria-labelledby="features-title" class="mt-12">
    <h2 id="features-title" class="text-2xl font-bold text-slate-900">Hệ thống làm được gì</h2>
    <div class="grid sm:grid-cols-3 gap-5 mt-5">
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900"><i class="fa-solid fa-ticket text-brand-600 mr-2" aria-hidden="true"></i>Phiếu hỗ trợ</h3>
            <p class="text-sm text-slate-700 mt-2">Tạo phiếu, đính kèm ảnh chụp lỗi, trao đổi với nhân viên IT và nhận thông báo khi trạng thái thay đổi.</p>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900"><i class="fa-solid fa-stopwatch text-brand-600 mr-2" aria-hidden="true"></i>Cam kết SLA</h3>
            <p class="text-sm text-slate-700 mt-2">Hạn xử lý tính theo giờ làm việc, tạm dừng khi chờ bạn phản hồi và tự leo thang khi quá hạn. <a class="text-brand-700 underline" href="{{ route('public.sla') }}">Xem chi tiết</a>.</p>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900"><i class="fa-solid fa-laptop-house text-brand-600 mr-2" aria-hidden="true"></i>Tài sản IT</h3>
            <p class="text-sm text-slate-700 mt-2">Kiểm kê thiết bị theo cây cha-con, bàn giao, thu hồi và lịch sử bảo trì đầy đủ.</p>
        </article>
    </div>
</section>

<section aria-labelledby="roles-title" class="mt-12">
    <h2 id="roles-title" class="text-2xl font-bold text-slate-900">Ba vai trò sử dụng</h2>
    <ul class="mt-4 space-y-2 text-slate-700">
        <li><strong>Nhân viên</strong>: gửi yêu cầu và theo dõi phiếu của mình.</li>
        <li><strong>Nhân viên IT (Technician)</strong>: nhận việc, xử lý, cập nhật trạng thái và lập bảo trì.</li>
        <li><strong>Quản trị viên (Admin)</strong>: điều phối phiếu, quản lý tài sản, người dùng, danh mục và xem báo cáo.</li>
    </ul>
</section>
@endsection
