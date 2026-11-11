<?php $__env->startSection('title', 'Báo cáo - ITAM Helpdesk'); ?>
<?php $__env->startSection('page_title', 'Báo cáo và thống kê'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8">
    <?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $labelKey = array_key_first($report['columns']);
            $barKey = array_key_exists('total', $report['columns']) ? 'total' : (array_key_exists('assigned', $report['columns']) ? 'assigned' : null);
            $max = $barKey ? max(1, collect($report['rows'])->max(fn ($r) => (float) ($r[$barKey] ?? 0))) : 1;
        ?>
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6" aria-labelledby="rep-<?php echo e($key); ?>">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 id="rep-<?php echo e($key); ?>" class="text-base font-bold text-slate-800"><?php echo e($report['title']); ?></h2>
                <a href="<?php echo e(route('reports.export', $key)); ?>" class="inline-flex items-center space-x-2 bg-white border border-slate-300 hover:border-brand-400 text-slate-700 font-medium px-3 py-1.5 rounded-xl text-xs">
                    <i class="fa-solid fa-file-csv" aria-hidden="true"></i><span>Xuất CSV<span class="sr-only">: <?php echo e($report['title']); ?></span></span>
                </a>
            </div>

            <?php if(count($report['rows']) === 0): ?>
                <p class="text-sm text-slate-500">Chưa có dữ liệu cho báo cáo này.</p>
            <?php else: ?>
                <?php if($barKey): ?>
                    <div class="space-y-2 mb-5" role="img" aria-label="Biểu đồ cột: <?php echo e($report['title']); ?>">
                        <?php $__currentLoopData = $report['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $value = (float) ($row[$barKey] ?? 0); ?>
                            <div class="flex items-center space-x-3 text-xs">
                                <span class="w-36 truncate text-slate-700" title="<?php echo e($row[$labelKey]); ?>"><?php echo e($row[$labelKey]); ?></span>
                                <span class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden"><span class="block h-3 bg-brand-600 rounded-full" style="width: <?php echo e(round($value / $max * 100)); ?>%"></span></span>
                                <span class="w-14 text-right font-semibold text-slate-700"><?php echo e(number_format($value, 0, ',', '.')); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only"><?php echo e($report['title']); ?></caption>
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr><?php $__currentLoopData = $report['columns']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th scope="col" class="px-3 py-2"><?php echo e($label); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $__currentLoopData = $report['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <?php $__currentLoopData = array_keys($report['columns']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <td class="px-3 py-2"><?php echo e(is_numeric($row[$col] ?? null) && $col !== 'month' ? number_format((float) $row[$col], is_float($row[$col] + 0) ? 1 : 0, ',', '.') : ($row[$col] ?? '')); ?></td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/reports/index.blade.php ENDPATH**/ ?>