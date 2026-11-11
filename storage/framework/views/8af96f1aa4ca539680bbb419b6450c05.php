<!DOCTYPE html>
<html lang="vi">
<?php
    $authUser = Auth::user();
    $isEmployee = ! $authUser->isStaff();

    // Mỗi vai trò có bảng màu, menu và tên gọi riêng.
    if ($authUser->isAdmin()) {
        $paletteName = 'indigo';
        $roleLabel = 'Quản trị viên';
        $roleIcon = 'fa-user-shield';
        $menu = [
            ['home', 'fa-chart-pie', 'Tổng quan', ['home']],
            ['tickets.index', 'fa-ticket', 'Quản lý phiếu', ['tickets.*']],
            ['assets.index', 'fa-laptop-house', 'Tài sản IT', ['assets.*']],
            ['reports.index', 'fa-chart-column', 'Báo cáo', ['reports.*']],
            ['admin.users.index', 'fa-users', 'Người dùng', ['admin.users.*']],
            ['admin.catalog.index', 'fa-folder-tree', 'Danh mục', ['admin.catalog.*'], ['departments']],
            ['admin.audit.index', 'fa-clipboard-list', 'Nhật ký hệ thống', ['admin.audit.*']],
        ];
    } elseif ($authUser->isTechnician()) {
        $paletteName = 'teal';
        $roleLabel = 'Nhân viên IT';
        $roleIcon = 'fa-screwdriver-wrench';
        $menu = [
            ['home', 'fa-gauge-high', 'Bảng công việc', ['home']],
            ['tickets.index', 'fa-list-check', 'Phiếu cần xử lý', ['tickets.*']],
            ['assets.index', 'fa-laptop-house', 'Tra cứu tài sản', ['assets.*']],
        ];
    } else {
        $paletteName = 'sky';
        $roleLabel = 'Nhân viên';
        $roleIcon = 'fa-user';
        $menu = [
            ['home', 'fa-house', 'Trang chủ', ['home']],
            ['tickets.index', 'fa-ticket', 'Phiếu của tôi', ['tickets.index', 'tickets.show']],
            ['tickets.create', 'fa-circle-plus', 'Gửi yêu cầu', ['tickets.create']],
        ];
    }
    $menu = array_map(fn ($item) => $item + [4 => []], $menu);
?>
<head>
    <?php echo $__env->make('partials.head', ['paletteName' => $paletteName], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $__env->yieldContent('title', 'ITAM & Helpdesk'); ?></title>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:text-brand-700 focus:px-4 focus:py-2 focus:rounded-lg">Bỏ qua điều hướng, tới nội dung chính</a>

<?php if($isEmployee): ?>
    
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-8">
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center space-x-2.5 font-bold text-slate-900">
                        <span class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center"><i class="fa-solid fa-headset"></i></span>
                        <span class="hidden sm:inline">ITAM Helpdesk</span>
                    </a>
                    <nav class="flex items-center space-x-1" aria-label="Điều hướng chính">
                        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$route, $icon, $label, $patterns, $params]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route($route, $params)); ?>"
                               class="px-3 py-2 rounded-lg text-sm font-medium flex items-center space-x-2 transition-colors <?php echo e(request()->routeIs(...$patterns) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'); ?>">
                                <i class="fa-solid <?php echo e($icon); ?> text-xs"></i><span class="hidden sm:inline"><?php echo e($label); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </nav>
                </div>
                <div class="flex items-center space-x-3">
                    <?php echo $__env->make('partials.bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <a href="<?php echo e(route('password.change')); ?>" class="p-2 text-slate-500 hover:text-brand-600" title="Đổi mật khẩu" aria-label="Đổi mật khẩu"><i class="fa-solid fa-key" aria-hidden="true"></i></a>
                    <div class="text-right hidden sm:block leading-tight">
                        <p class="text-sm font-semibold text-slate-800"><?php echo e($authUser->name); ?></p>
                        <p class="text-xs text-slate-400"><?php echo e($roleLabel); ?></p>
                    </div>
                    <span class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm"><?php echo e(mb_strtoupper(mb_substr($authUser->name, 0, 1))); ?></span>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-500" title="Đăng xuất" aria-label="Đăng xuất"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></button>
                    </form>
                </div>
            </div>
        </header>

        <main id="main" class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h1 class="text-2xl font-bold text-slate-900"><?php echo $__env->yieldContent('page_title', 'Trang chủ'); ?></h1>
                <div class="flex items-center space-x-3"><?php echo $__env->yieldContent('header_actions'); ?></div>
            </div>
            <?php echo $__env->make('partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
<?php else: ?>
    
    <div class="min-h-screen flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-800">
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center space-x-3 text-white font-bold text-lg">
                        <span class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/30"><i class="fa-solid fa-headset"></i></span>
                        <span>ITAM Helpdesk</span>
                    </a>
                    <span class="mt-4 inline-flex items-center space-x-2 px-2.5 py-1 rounded-full bg-brand-500/15 text-brand-300 text-xs font-semibold">
                        <i class="fa-solid <?php echo e($roleIcon); ?>"></i><span><?php echo e($roleLabel); ?></span>
                    </span>
                </div>
                <nav class="p-4 space-y-1" aria-label="Điều hướng chính">
                    <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$route, $icon, $label, $patterns, $params]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route($route, $params)); ?>"
                           class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors <?php echo e(request()->routeIs(...$patterns) ? 'bg-brand-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'); ?>">
                            <i class="fa-solid <?php echo e($icon); ?> w-5"></i><span><?php echo e($label); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            </div>

            <div class="p-4 border-t border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3 min-w-0">
                    <span class="w-9 h-9 rounded-full bg-brand-500/20 text-brand-300 flex items-center justify-center font-bold text-sm flex-shrink-0"><?php echo e(mb_strtoupper(mb_substr($authUser->name, 0, 1))); ?></span>
                    <div class="text-xs min-w-0">
                        <p class="font-semibold text-white truncate"><?php echo e($authUser->name); ?></p>
                        <p class="text-slate-400 truncate"><?php echo e($authUser->email); ?></p>
                    </div>
                </div>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-slate-400 hover:text-rose-400 p-2" title="Đăng xuất" aria-label="Đăng xuất"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></button>
                </form>
            </div>
        </aside>

        <main id="main" class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-bold text-slate-800"><?php echo $__env->yieldContent('page_title', 'Tổng quan'); ?></h1>
                <div class="flex items-center space-x-3">
                    <?php echo $__env->yieldContent('header_actions'); ?>
                    <?php echo $__env->make('partials.bell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <a href="<?php echo e(route('password.change')); ?>" class="p-2 text-slate-500 hover:text-brand-600" title="Đổi mật khẩu" aria-label="Đổi mật khẩu"><i class="fa-solid fa-key" aria-hidden="true"></i></a>
                </div>
            </header>
            <div class="p-6 flex-1">
                <?php echo $__env->make('partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>
<?php endif; ?>
<?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>