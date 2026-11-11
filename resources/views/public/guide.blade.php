@extends('layouts.public')

@section('title', 'Hướng dẫn gửi yêu cầu hỗ trợ')
@section('meta_description', 'Hướng dẫn gửi yêu cầu hỗ trợ CNTT hiệu quả: mô tả sự cố, chọn mức ưu tiên, đính kèm ảnh chụp và theo dõi tiến độ.')

@section('content')
<h1 class="text-3xl font-bold text-slate-900">Hướng dẫn gửi yêu cầu hỗ trợ</h1>

<h2 class="text-xl font-bold text-slate-900 mt-8">Các bước</h2>
<ol class="mt-3 list-decimal list-inside space-y-2 text-slate-700">
    <li><a class="text-brand-700 underline" href="{{ route('login') }}">Đăng nhập</a> hoặc <a class="text-brand-700 underline" href="{{ route('register') }}">đăng ký</a> tài khoản nhân viên.</li>
    <li>Chọn <em>Gửi yêu cầu</em>, nhập tiêu đề và mô tả sự cố; chọn danh mục, mức ưu tiên và thiết bị liên quan (nếu có).</li>
    <li>Đính kèm ảnh chụp màn hình hoặc tệp nhật ký (JPG, PNG, PDF, TXT; tối đa 5 MB).</li>
    <li>Theo dõi trạng thái và bình luận ngay trên phiếu; hệ thống thông báo khi có thay đổi.</li>
</ol>

<h2 class="text-xl font-bold text-slate-900 mt-8">Câu hỏi thường gặp</h2>
<dl class="mt-3 space-y-4 text-slate-700">
    <div><dt class="font-semibold">Tôi quên mật khẩu thì sao?</dt><dd>Dùng liên kết <a class="text-brand-700 underline" href="{{ route('password.request') }}">Quên mật khẩu</a> ở trang đăng nhập.</dd></div>
    <div><dt class="font-semibold">Vì sao phiếu của tôi "Chờ người dùng phản hồi"?</dt><dd>Nhân viên IT cần thêm thông tin từ bạn. Hãy bình luận vào phiếu để việc xử lý tiếp tục.</dd></div>
    <div><dt class="font-semibold">Dữ liệu trong hệ thống có thật không?</dt><dd>Không. Đây là hệ thống phục vụ học tập, toàn bộ dữ liệu là dữ liệu mô phỏng.</dd></div>
</dl>
@endsection
