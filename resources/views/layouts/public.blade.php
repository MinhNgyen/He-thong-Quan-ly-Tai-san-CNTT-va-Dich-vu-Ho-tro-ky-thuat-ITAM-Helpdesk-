<!DOCTYPE html>
<html lang="vi">
<head>
    @include('partials.head', ['paletteName' => 'indigo'])
    <title>@yield('title', 'ITAM & Helpdesk') | ITAM Helpdesk</title>
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:site_name" content="ITAM Helpdesk">
    <meta property="og:title" content="@yield('title', 'ITAM & Helpdesk')">
    <meta property="og:description" content="@yield('meta_description', 'Hệ thống quản lý tài sản CNTT và dịch vụ hỗ trợ kỹ thuật.')">
    <meta property="og:url" content="{{ url()->current() }}">
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg">Bỏ qua điều hướng, tới nội dung chính</a>
<div class="min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 font-bold text-slate-900">
                <span class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                <span>ITAM Helpdesk</span>
            </a>
            <nav aria-label="Điều hướng công khai" class="flex items-center space-x-1 text-sm">
                <a href="{{ route('landing') }}" @if (request()->routeIs('landing', 'home')) aria-current="page" @endif class="px-3 py-2 rounded-lg hover:bg-slate-100">Giới thiệu</a>
                <a href="{{ route('public.sla') }}" @if (request()->routeIs('public.sla')) aria-current="page" @endif class="px-3 py-2 rounded-lg hover:bg-slate-100">Cam kết SLA</a>
                <a href="{{ route('public.guide') }}" @if (request()->routeIs('public.guide')) aria-current="page" @endif class="px-3 py-2 rounded-lg hover:bg-slate-100">Hướng dẫn</a>
                @auth
                    <a href="{{ url('/') }}" class="px-4 py-2 rounded-lg bg-brand-600 text-white font-medium">Vào hệ thống</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg bg-brand-600 text-white font-medium">Đăng nhập</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main" class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-10">
        @yield('content')
    </main>

    @include('partials.footer')
</div>
</body>
</html>
