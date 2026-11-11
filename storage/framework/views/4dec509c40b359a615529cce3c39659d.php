<!DOCTYPE html>
<html lang="vi">
<head>
    <?php echo $__env->make('partials.head', ['paletteName' => 'indigo'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <title><?php echo $__env->yieldContent('title', 'ITAM & Helpdesk'); ?></title>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg">Bỏ qua tới nội dung chính</a>
<div class="min-h-screen grid lg:grid-cols-2">
    
    <aside class="hidden lg:flex flex-col justify-between p-12 text-white bg-gradient-to-br from-brand-700 via-brand-600 to-brand-900 relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-black/10 blur-2xl"></div>

        <div class="relative flex items-center space-x-3 text-xl font-bold">
            <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center">
                <i class="fa-solid fa-headset"></i>
            </div>
            <span>ITAM Helpdesk</span>
        </div>

        <div class="relative space-y-8">
            <div>
                <h2 class="text-4xl font-bold leading-tight">Một nơi cho mọi yêu cầu<br>hỗ trợ CNTT và tài sản IT.</h2>
                <p class="mt-4 text-brand-100 text-base max-w-md">Gửi yêu cầu, theo dõi tiến độ xử lý và quản lý thiết bị trong cùng một hệ thống.</p>
            </div>

            <ul class="space-y-4 text-sm">
                <li class="flex items-start space-x-3">
                    <span class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-user"></i></span>
                    <span><strong class="block text-white">Nhân viên</strong><span class="text-brand-100">Gửi yêu cầu và theo dõi phiếu của mình.</span></span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                    <span><strong class="block text-white">Nhân viên IT</strong><span class="text-brand-100">Nhận việc, xử lý và cập nhật trạng thái.</span></span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-user-shield"></i></span>
                    <span><strong class="block text-white">Quản trị viên</strong><span class="text-brand-100">Điều phối phiếu và quản lý toàn bộ tài sản.</span></span>
                </li>
            </ul>
        </div>

        <p class="relative text-xs text-brand-200">Đồ án CSE702051 · Thiết kế Web nâng cao · Đại học Phenikaa</p>
    </aside>

    
    <main id="main" class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center space-x-3 mb-8 text-lg font-bold text-slate-800">
                <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center"><i class="fa-solid fa-headset"></i></div>
                <span>ITAM Helpdesk</span>
            </div>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </main>
</div>
<?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/guest.blade.php ENDPATH**/ ?>