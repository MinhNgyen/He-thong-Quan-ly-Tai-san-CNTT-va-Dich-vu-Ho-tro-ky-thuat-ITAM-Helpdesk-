<?php $__env->startSection('title', 'Danh sách Ticket - ITAM & Helpdesk'); ?>

<?php $__env->startSection('page_title'); ?>
    <?php if(Auth::user()->isTechnician()): ?>
        Ticket được phân công cho tôi
    <?php elseif(Auth::user()->isAdmin()): ?>
        Quản lý tất cả Ticket
    <?php else: ?>
        Ticket của tôi
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('header_actions'); ?>
    <?php if(!Auth::user()->isTechnician()): ?>
        <a href="<?php echo e(route('tickets.create')); ?>" class="inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-medium px-4 py-2 rounded-xl text-sm transition-all shadow-sm shadow-brand-200">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tạo Ticket mới</span>
        </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <!-- Filters Header -->
    <div class="p-4 border-b border-slate-200/80 bg-slate-50/50 flex flex-wrap gap-3 justify-between items-center">
        <form method="GET" action="<?php echo e(route('tickets.index')); ?>" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="Tìm theo mã hoặc tiêu đề..." 
                class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 w-60">
            
            <select name="status" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="open" <?php echo e(request('status') == 'open' ? 'selected' : ''); ?>>Mới</option>
                <option value="assigned" <?php echo e(request('status') == 'assigned' ? 'selected' : ''); ?>>Đã phân công</option>
                <option value="in_progress" <?php echo e(request('status') == 'in_progress' ? 'selected' : ''); ?>>Đang xử lý</option>
                <option value="waiting_user" <?php echo e(request('status') == 'waiting_user' ? 'selected' : ''); ?>>Chờ người dùng phản hồi</option>
                <option value="resolved" <?php echo e(request('status') == 'resolved' ? 'selected' : ''); ?>>Đã giải quyết</option>
                <option value="closed" <?php echo e(request('status') == 'closed' ? 'selected' : ''); ?>>Đã đóng</option>
                <option value="cancelled" <?php echo e(request('status') == 'cancelled' ? 'selected' : ''); ?>>Đã hủy</option>
            </select>

            <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-xl text-xs transition-all">
                Lọc
            </button>
        </form>

        <div class="text-xs text-slate-500 font-medium">
            Tổng cộng: <strong class="text-slate-800"><?php echo e($tickets->total()); ?></strong> phiếu
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-xs">
                <tr>
                    <th class="py-3.5 px-6">Mã & Tiêu đề</th>
                    <th class="py-3.5 px-6">Danh mục</th>
                    <th class="py-3.5 px-6">Trạng thái</th>
                    <th class="py-3.5 px-6">Độ ưu tiên</th>
                    <th class="py-3.5 px-6">Người tạo</th>
                    <th class="py-3.5 px-6 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-4 px-6">
                        <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="font-semibold text-slate-800 hover:text-brand-600 block">
                            <?php echo e($ticket->ticket_code ?? '#HD-'.$ticket->id); ?> - <?php echo e($ticket->title); ?>

                        </a>
                        <span class="text-xs text-slate-400 block mt-0.5"><?php echo e($ticket->created_at->diffForHumans()); ?></span>
                    </td>
                    <td class="py-4 px-6 text-slate-600 font-medium">
                        <?php echo e($ticket->category->name ?? 'Chung'); ?>

                    </td>
                    <td class="py-4 px-6">
                        <?php echo $__env->make('partials.status-badge', ['status' => $ticket->status], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </td>
                    <td class="py-4 px-6">
                        <span class="font-medium <?php echo e($ticket->priority == 'urgent' ? 'text-rose-600 font-bold' : ($ticket->priority == 'high' ? 'text-orange-600' : 'text-slate-600')); ?>">
                            ● <?php echo e(\App\Enums\TicketPriority::tryFrom($ticket->priority)?->label() ?? $ticket->priority); ?>

                        </span>
                    </td>
                    <td class="py-4 px-6 text-slate-600">
                        <?php echo e($ticket->createdBy->name ?? 'N/A'); ?>

                    </td>
                    <td class="py-4 px-6 text-right">
                        <a href="<?php echo e(route('tickets.show', $ticket)); ?>" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-brand-50 hover:text-brand-600 text-slate-700 rounded-lg font-medium text-xs transition-colors">
                            Chi tiết
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="py-10 text-center text-slate-400">
                        Chưa có phiếu hỗ trợ nào phù hợp.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-200/80">
        <?php echo e($tickets->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/tickets/index.blade.php ENDPATH**/ ?>