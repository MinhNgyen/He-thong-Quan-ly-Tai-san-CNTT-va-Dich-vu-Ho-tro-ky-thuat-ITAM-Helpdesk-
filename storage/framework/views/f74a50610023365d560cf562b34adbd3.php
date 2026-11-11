<?php $__env->startSection('title', 'Trang chủ - ITAM Helpdesk'); ?>
<?php $__env->startSection('page_title', 'Trung tâm hỗ trợ'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8">
    <div class="rounded-3xl p-8 text-white bg-gradient-to-br from-brand-600 to-brand-800 flex flex-wrap items-center justify-between gap-6">
        <div class="max-w-xl">
            <p class="text-brand-100 text-sm">Xin chào, <?php echo e(Auth::user()->name); ?> 👋</p>
            <h2 class="text-2xl font-bold mt-1">Bạn đang gặp sự cố về máy tính hay thiết bị?</h2>
            <p class="text-brand-100 text-sm mt-2">Gửi yêu cầu cho bộ phận IT, chúng tôi sẽ tiếp nhận và cập nhật tiến độ cho bạn.</p>
        </div>
        <a href="<?php echo e(route('tickets.create')); ?>" class="inline-flex items-center space-x-2 bg-white text-brand-700 hover:bg-brand-50 font-semibold px-6 py-3 rounded-xl shadow-lg">
            <i class="fa-solid fa-circle-plus"></i><span>Gửi yêu cầu hỗ trợ</span>
        </a>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <?php $__currentLoopData = [
            ['Yêu cầu đã gửi', $dashboard['total'], 'fa-paper-plane', 'bg-brand-50 text-brand-600'],
            ['Đang được xử lý', $dashboard['open'], 'fa-hourglass-half', 'bg-amber-50 text-amber-600'],
            ['Đã giải quyết', $dashboard['resolved'], 'fa-circle-check', 'bg-emerald-50 text-emerald-600'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center space-x-4">
                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl <?php echo e($tone); ?>"><i class="fa-solid <?php echo e($icon); ?>"></i></span>
                <div>
                    <p class="text-2xl font-bold text-slate-800 leading-none"><?php echo e($value); ?></p>
                    <p class="text-xs text-slate-500 mt-1"><?php echo e($label); ?></p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-slate-800">Yêu cầu gần đây của bạn</h3>
                <a href="<?php echo e(route('tickets.index')); ?>" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Xem tất cả <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
            </div>
            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $dashboard['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="block bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-300 transition-colors">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-mono font-bold text-slate-400"><?php echo e($ticket->ticket_code); ?></span>
                            <?php echo $__env->make('partials.status-badge', ['status' => $ticket->status], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <p class="font-semibold text-slate-800 mt-1.5"><?php echo e($ticket->title); ?></p>
                        <p class="text-xs text-slate-400 mt-1">
                            Gửi <?php echo e($ticket->created_at?->diffForHumans()); ?>

                            <?php if($ticket->assignedTo): ?> · Người xử lý: <span class="font-medium text-slate-600"><?php echo e($ticket->assignedTo->name); ?></span>
                            <?php else: ?> · Đang chờ tiếp nhận <?php endif; ?>
                        </p>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="bg-white p-10 rounded-2xl border border-dashed border-slate-300 text-center">
                        <i class="fa-regular fa-comment-dots text-3xl text-slate-300"></i>
                        <p class="text-sm text-slate-500 mt-3">Bạn chưa gửi yêu cầu nào.</p>
                        <a href="<?php echo e(route('tickets.create')); ?>" class="inline-block mt-3 text-sm font-semibold text-brand-600">Gửi yêu cầu đầu tiên</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 h-fit">
            <h3 class="font-bold text-slate-800 mb-4">Quy trình hỗ trợ</h3>
            <ol class="space-y-4 text-sm">
                <?php $__currentLoopData = [
                    ['Gửi yêu cầu', 'Mô tả sự cố và chọn mức độ ưu tiên.'],
                    ['IT tiếp nhận', 'Một nhân viên IT được phân công hoặc tự nhận việc.'],
                    ['Xử lý & trao đổi', 'Theo dõi tiến độ và bình luận ngay trên phiếu.'],
                    ['Hoàn tất', 'Phiếu được giải quyết và đóng lại.'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$title, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-start space-x-3">
                        <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center flex-shrink-0"><?php echo e($i + 1); ?></span>
                        <span><strong class="text-slate-800"><?php echo e($title); ?></strong><br><span class="text-slate-500 text-xs"><?php echo e($desc); ?></span></span>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard/employee.blade.php ENDPATH**/ ?>