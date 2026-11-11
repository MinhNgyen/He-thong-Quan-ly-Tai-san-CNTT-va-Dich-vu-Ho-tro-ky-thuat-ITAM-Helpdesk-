<?php $__env->startSection('title', 'Ticket ' . ($ticket->ticket_code ?? '#'.$ticket->id)); ?>
<?php $__env->startSection('page_title', 'Chi tiết phiếu hỗ trợ'); ?>

<?php $__env->startSection('header_actions'); ?>
    <a href="<?php echo e(route('tickets.index')); ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm transition-all">
        <i class="fa-solid fa-arrow-left text-xs mr-1"></i> Quay lại
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <!-- Ticket Title Banner -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap justify-between items-start gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <span class="font-mono text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">
                    <?php echo e($ticket->ticket_code ?? '#HD-'.$ticket->id); ?>

                </span>
                <?php echo $__env->make('partials.status-badge', ['status' => $ticket->status], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <h1 class="text-xl font-bold text-slate-800 mb-1"><?php echo e($ticket->title); ?></h1>
            <p class="text-xs text-slate-400">
                Tạo bởi <span class="font-semibold text-slate-600"><?php echo e($ticket->createdBy->name ?? 'N/A'); ?></span> • <?php echo e($ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : ''); ?>

            </p>
        </div>

        <?php if($canClaim ?? false): ?>
            <form method="POST" action="<?php echo e(route('tickets.claim', $ticket)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-xl text-xs shadow-sm flex items-center space-x-2">
                    <i class="fa-solid fa-hand"></i>
                    <span>Tự nhận xử lý</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Mô tả sự cố</h3>
                <div class="text-sm text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 min-h-[80px]">
                    <?php echo nl2br(e($ticket->description)); ?>

                </div>
            </div>

            <!-- Attachments -->
            <section class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3" aria-labelledby="attach-title">
                <h3 id="attach-title" class="text-base font-bold text-slate-800 flex items-center space-x-2">
                    <i class="fa-solid fa-paperclip text-brand-600" aria-hidden="true"></i><span>Tệp đính kèm</span>
                </h3>
                <ul class="divide-y divide-slate-100 text-sm">
                    <?php $__empty_1 = true; $__currentLoopData = $ticket->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="py-2 flex items-center justify-between gap-3">
                            <span class="min-w-0">
                                <a href="<?php echo e(route('attachments.download', $file)); ?>" class="text-brand-700 hover:underline break-all"><?php echo e($file->original_name); ?></a>
                                <span class="block text-xs text-slate-500"><?php echo e(number_format($file->size / 1024, 0, ',', '.')); ?> KB · <?php echo e($file->user?->name); ?> · <?php echo e($file->created_at->format('d/m/Y H:i')); ?></span>
                            </span>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $file)): ?>
                                <form method="POST" action="<?php echo e(route('attachments.destroy', $file)); ?>" onsubmit="return confirm('Xóa tệp này?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-rose-700 hover:underline text-xs">Xóa<span class="sr-only"> <?php echo e($file->original_name); ?></span></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="py-2 text-slate-500">Chưa có tệp đính kèm.</li>
                    <?php endif; ?>
                </ul>
                <?php if($canComment ?? false): ?>
                    <form method="POST" action="<?php echo e(route('tickets.attachments.store', $ticket)); ?>" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 pt-2">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label for="file" class="block text-xs font-medium text-slate-700 mb-1">Chọn tệp (JPG, PNG, PDF, TXT; tối đa 5 MB)</label>
                            <input id="file" type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf,.txt" class="text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium text-xs rounded-xl">Tải lên</button>
                    </form>
                <?php endif; ?>
            </section>

            <!-- Comments -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-800 flex items-center space-x-2">
                    <i class="fa-regular fa-comments text-brand-600"></i>
                    <span>Trao đổi & Bình luận</span>
                </h3>

                <div class="space-y-3">
                    <?php $__empty_1 = true; $__currentLoopData = $ticket->comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold text-slate-800"><?php echo e($comment->user->name ?? 'User'); ?></span>
                                <span class="text-[11px] text-slate-400"><?php echo e($comment->created_at->format('d/m/Y H:i')); ?></span>
                            </div>
                            <p class="text-sm text-slate-600 whitespace-pre-line"><?php echo e($comment->content); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-xl text-center">Chưa có bình luận nào.</p>
                    <?php endif; ?>
                </div>

                <?php if($canComment ?? false): ?>
                <form method="POST" action="<?php echo e(route('tickets.comments.store', $ticket)); ?>" class="space-y-3 pt-2">
                    <?php echo csrf_field(); ?>
                    <textarea name="content" rows="3" required placeholder="Nhập nội dung trao đổi..."
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                    <button type="submit" class="px-4 py-2 bg-brand-600 text-white font-medium text-xs rounded-xl shadow-sm">
                        Gửi bình luận
                    </button>
                </form>
                <?php else: ?>
                    <p class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-xl text-center">Phiếu đã kết thúc, không thể bình luận thêm.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="space-y-6">
            <!-- SLA -->
            <?php
                $slaLabels = ['running' => ['Đang chạy', 'bg-sky-50 text-sky-800 border-sky-200'], 'paused' => ['Tạm dừng (chờ người dùng phản hồi)', 'bg-orange-50 text-orange-800 border-orange-200'], 'met' => ['Đạt SLA', 'bg-emerald-50 text-emerald-800 border-emerald-200'], 'breached' => ['Quá hạn SLA', 'bg-rose-50 text-rose-800 border-rose-200'], 'none' => ['Không áp dụng', 'bg-slate-50 text-slate-700 border-slate-200']];
                [$slaText, $slaClass] = $slaLabels[$sla['state']];
                $hours = $sla['remaining_minutes'] !== null ? intdiv($sla['remaining_minutes'], 60) . ' giờ ' . ($sla['remaining_minutes'] % 60) . ' phút' : null;
            ?>
            <section class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-2" aria-labelledby="sla-title">
                <h3 id="sla-title" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Cam kết SLA (giờ làm việc)</h3>
                <p class="inline-flex px-3 py-1 rounded-full border text-xs font-semibold <?php echo e($slaClass); ?>"><?php echo e($slaText); ?></p>
                <?php if($sla['due_at']): ?><p class="text-sm text-slate-700">Hạn xử lý: <strong><?php echo e($sla['due_at']->copy()->timezone(config('sla.timezone'))->format('H:i d/m/Y')); ?></strong></p><?php endif; ?>
                <?php if($sla['state'] === 'running' && $hours): ?><p class="text-sm text-slate-700">Còn lại: <strong><?php echo e($hours); ?></strong> làm việc</p><?php endif; ?>
                <?php if($sla['state'] === 'paused' && $hours): ?><p class="text-sm text-slate-700">Thời gian còn lại khi tiếp tục: <strong><?php echo e($hours); ?></strong></p><?php endif; ?>
                <?php if($ticket->sla_escalated_at): ?><p class="text-xs text-rose-700">Đã leo thang lúc <?php echo e($ticket->sla_escalated_at->copy()->timezone(config('sla.timezone'))->format('H:i d/m/Y')); ?>.</p><?php endif; ?>
            </section>

            <!-- Update / Assign Panel -->
            <?php if($canUpdate ?? false): ?>
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cập nhật Ticket</h3>
                <form method="POST" action="<?php echo e(route('tickets.update', $ticket)); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>

                    <?php if($canAssign ?? false): ?>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Nhân viên phụ trách</label>
                        <select name="assigned_to" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">-- Chưa phân công --</option>
                            <?php $__currentLoopData = $technicians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tech): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tech->id); ?>" <?php echo e($ticket->assigned_to == $tech->id ? 'selected' : ''); ?>>
                                    <?php echo e($tech->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Trạng thái xử lý</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e($ticket->status === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all">
                        Lưu thay đổi
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Info Panel -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-xs space-y-3">
                <h3 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Thông tin phiếu</h3>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400">Danh mục:</span>
                    <span class="font-semibold text-slate-700"><?php echo e($ticket->category->name ?? 'Mặc định'); ?></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400">Độ ưu tiên:</span>
                    <span class="font-bold <?php echo e($ticket->priority == 'urgent' ? 'text-rose-600' : 'text-slate-700'); ?>"><?php echo e(ucfirst($ticket->priority)); ?></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400">Người xử lý:</span>
                    <span class="font-semibold text-brand-600"><?php echo e($ticket->assignedTo->name ?? 'Chưa phân công'); ?></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400">Tài sản liên quan:</span>
                    <span class="font-mono text-slate-700"><?php echo e($ticket->asset->asset_code ?? $ticket->asset->name ?? 'Không có'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/tickets/show.blade.php ENDPATH**/ ?>