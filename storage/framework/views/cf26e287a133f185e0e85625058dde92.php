<?php $__env->startSection('title', 'Quản trị - ITAM Helpdesk'); ?>
<?php $__env->startSection('page_title', 'Bảng điều khiển quản trị'); ?>

<?php $__env->startSection('header_actions'); ?>
    <a href="<?php echo e(route('assets.create')); ?>" class="inline-flex items-center space-x-2 bg-white border border-slate-200 hover:border-brand-300 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm">
        <i class="fa-solid fa-laptop text-xs"></i><span>Thêm tài sản</span>
    </a>
    <a href="<?php echo e(route('tickets.create')); ?>" class="inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-medium px-4 py-2 rounded-xl text-sm shadow-sm">
        <i class="fa-solid fa-plus text-xs"></i><span>Tạo phiếu</span>
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $byStatus = $dashboard['by_status'];
    $maxStatus = max(1, max($byStatus));
    $barColors = ['open' => 'bg-sky-500', 'assigned' => 'bg-violet-500', 'in_progress' => 'bg-amber-500', 'waiting_user' => 'bg-orange-500', 'resolved' => 'bg-emerald-500', 'closed' => 'bg-slate-400', 'cancelled' => 'bg-rose-400'];
    $assetTotal = array_sum($assetStats);
    $assetTone = ['available' => 'text-emerald-600 bg-emerald-50', 'in_use' => 'text-brand-600 bg-brand-50', 'maintenance' => 'text-amber-600 bg-amber-50', 'retired' => 'text-slate-500 bg-slate-100'];
?>
<div class="space-y-6">
    
    <div class="rounded-2xl p-6 text-white bg-gradient-to-r from-brand-700 to-brand-500 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-brand-100 text-sm">Xin chào, <?php echo e(Auth::user()->name); ?></p>
            <?php if($dashboard['unassigned_count'] > 0): ?>
                <h2 class="text-xl font-bold mt-1">Có <?php echo e($dashboard['unassigned_count']); ?> phiếu mới đang chờ phân công</h2>
            <?php else: ?>
                <h2 class="text-xl font-bold mt-1">Mọi phiếu mới đều đã có người phụ trách</h2>
            <?php endif; ?>
        </div>
        <a href="<?php echo e(route('tickets.index', ['status' => 'open'])); ?>" class="inline-flex items-center space-x-2 bg-white/15 hover:bg-white/25 px-4 py-2 rounded-xl text-sm font-semibold">
            <span>Xem phiếu mới</span><i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>

    
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php $__currentLoopData = [
            ['Tổng số phiếu', $dashboard['total'], 'fa-ticket', 'bg-brand-50 text-brand-600'],
            ['Đang xử lý', $dashboard['open'], 'fa-clock', 'bg-amber-50 text-amber-600'],
            ['Đã giải quyết', $dashboard['resolved'], 'fa-circle-check', 'bg-emerald-50 text-emerald-600'],
            ['Tổng tài sản', $assetTotal, 'fa-laptop', 'bg-sky-50 text-sky-600'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase"><?php echo e($label); ?></p>
                    <p class="text-3xl font-bold text-slate-800 mt-1"><?php echo e($value); ?></p>
                </div>
                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl <?php echo e($tone); ?>"><i class="fa-solid <?php echo e($icon); ?>"></i></span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Phiếu chờ phân công</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700"><?php echo e($dashboard['unassigned_count']); ?> phiếu</span>
            </div>
            <ul class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $dashboard['unassigned']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="p-4 px-5 flex items-center justify-between gap-4 hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="text-xs font-mono font-bold text-slate-500"><?php echo e($ticket->ticket_code); ?></p>
                            <p class="font-semibold text-slate-800 truncate"><?php echo e($ticket->title); ?></p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?php echo e($ticket->createdBy->name ?? 'N/A'); ?> · <?php echo e($ticket->created_at?->diffForHumans()); ?>

                                · <?php echo $__env->make('partials.priority-badge', ['priority' => $ticket->priority], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </p>
                        </div>
                        <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="flex-shrink-0 px-3.5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold">Phân công</a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="p-10 text-center text-sm text-slate-400"><i class="fa-regular fa-face-smile text-2xl block mb-2"></i>Không có phiếu nào đang chờ.</li>
                <?php endif; ?>
            </ul>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <h3 class="font-bold text-slate-800 mb-4">Phiếu theo trạng thái</h3>
            <div class="space-y-3">
                <?php $__currentLoopData = \App\Enums\TicketStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-600"><?php echo e($status->label()); ?></span>
                            <span class="font-semibold text-slate-800"><?php echo e($byStatus[$status->value]); ?></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full <?php echo e($barColors[$status->value]); ?>" style="width: <?php echo e(round($byStatus[$status->value] / $maxStatus * 100)); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-800">Tình trạng tài sản</h3>
            <a href="<?php echo e(route('assets.index')); ?>" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Quản lý tài sản <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php $__currentLoopData = \App\Enums\AssetStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('assets.index', ['status' => $status->value])); ?>" class="rounded-xl p-4 <?php echo e($assetTone[$status->value]); ?> hover:opacity-90">
                    <p class="text-2xl font-bold"><?php echo e($assetStats[$status->value]); ?></p>
                    <p class="text-xs font-medium mt-0.5"><?php echo e($status->label()); ?></p>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800">Phiếu gần đây</h3>
            <a href="<?php echo e(route('tickets.index')); ?>" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Xem tất cả <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                    <tr><th class="py-3 px-5">Mã</th><th class="py-3 px-5">Tiêu đề</th><th class="py-3 px-5">Người tạo</th><th class="py-3 px-5">Phụ trách</th><th class="py-3 px-5">Trạng thái</th><th class="py-3 px-5"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $dashboard['recent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-5 font-mono text-xs font-bold text-slate-600"><?php echo e($ticket->ticket_code); ?></td>
                            <td class="py-3.5 px-5 font-medium text-slate-800"><?php echo e($ticket->title); ?></td>
                            <td class="py-3.5 px-5 text-slate-600"><?php echo e($ticket->createdBy->name ?? 'N/A'); ?></td>
                            <td class="py-3.5 px-5 text-slate-600"><?php echo e($ticket->assignedTo->name ?? '—'); ?></td>
                            <td class="py-3.5 px-5"><?php echo $__env->make('partials.status-badge', ['status' => $ticket->status], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                            <td class="py-3.5 px-5 text-right"><a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Chi tiết</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="py-10 text-center text-slate-400">Chưa có phiếu hỗ trợ nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard/admin.blade.php ENDPATH**/ ?>