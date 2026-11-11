<?php $__env->startSection('title', 'Bảng công việc - ITAM Helpdesk'); ?>
<?php $__env->startSection('page_title', 'Bảng công việc của tôi'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="rounded-2xl p-6 text-white bg-gradient-to-r from-brand-700 to-brand-500">
        <p class="text-brand-100 text-sm">Xin chào, <?php echo e(Auth::user()->name); ?></p>
        <h2 class="text-xl font-bold mt-1">
            <?php if($dashboard['mine_count'] > 0): ?>
                Bạn đang phụ trách <?php echo e($dashboard['mine_count']); ?> phiếu cần xử lý
            <?php else: ?>
                Bạn chưa có phiếu nào đang xử lý
            <?php endif; ?>
        </h2>
        <p class="text-brand-100 text-sm mt-1">
            <?php if($dashboard['unassigned_count'] > 0): ?>
                Còn <?php echo e($dashboard['unassigned_count']); ?> phiếu mới chưa ai nhận, bạn có thể nhận việc ngay bên dưới.
            <?php else: ?>
                Hiện không có phiếu mới nào đang chờ nhận.
            <?php endif; ?>
        </p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <?php $__currentLoopData = [
            ['Đang xử lý của tôi', $dashboard['mine_count'], 'fa-screwdriver-wrench', 'bg-amber-50 text-amber-600'],
            ['Phiếu chờ nhận', $dashboard['unassigned_count'], 'fa-inbox', 'bg-sky-50 text-sky-600'],
            ['Tôi đã giải quyết', $dashboard['resolved_by_me'], 'fa-circle-check', 'bg-emerald-50 text-emerald-600'],
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

    <div class="grid lg:grid-cols-2 gap-6">
        
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800"><i class="fa-solid fa-screwdriver-wrench text-brand-500 mr-2"></i>Việc của tôi</h3>
                <a href="<?php echo e(route('tickets.index')); ?>" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Tất cả phiếu</a>
            </div>
            <ul class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $dashboard['mine']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li>
                        <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="block p-4 px-5 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-mono font-bold text-slate-500"><?php echo e($ticket->ticket_code); ?></p>
                                <?php echo $__env->make('partials.status-badge', ['status' => $ticket->status], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                            <p class="font-semibold text-slate-800 mt-1"><?php echo e($ticket->title); ?></p>
                            <p class="text-xs text-slate-400 mt-1"><?php echo e($ticket->createdBy->name ?? 'N/A'); ?> · cập nhật <?php echo e($ticket->updated_at?->diffForHumans()); ?> · <?php echo $__env->make('partials.priority-badge', ['priority' => $ticket->priority], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></p>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="p-10 text-center text-sm text-slate-400"><i class="fa-regular fa-circle-check text-2xl block mb-2"></i>Không có việc nào đang chờ bạn.</li>
                <?php endif; ?>
            </ul>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800"><i class="fa-solid fa-inbox text-sky-500 mr-2"></i>Phiếu mới chờ nhận</h3>
            </div>
            <ul class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $dashboard['unassigned']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="p-4 px-5 flex items-center justify-between gap-4">
                        <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="min-w-0 hover:opacity-80">
                            <p class="text-xs font-mono font-bold text-slate-500"><?php echo e($ticket->ticket_code); ?></p>
                            <p class="font-semibold text-slate-800 truncate"><?php echo e($ticket->title); ?></p>
                            <p class="text-xs text-slate-400 mt-0.5"><?php echo e($ticket->category->name ?? 'Chung'); ?> · <?php echo $__env->make('partials.priority-badge', ['priority' => $ticket->priority], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></p>
                        </a>
                        <form method="POST" action="<?php echo e(route('tickets.claim', $ticket)); ?>" class="flex-shrink-0">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-3.5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold">Nhận việc</button>
                        </form>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="p-10 text-center text-sm text-slate-400">Không có phiếu nào đang chờ nhận.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard/technician.blade.php ENDPATH**/ ?>