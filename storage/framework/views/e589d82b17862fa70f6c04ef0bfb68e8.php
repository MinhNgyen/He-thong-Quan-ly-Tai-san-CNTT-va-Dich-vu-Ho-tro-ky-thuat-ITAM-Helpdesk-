<?php if(session('success')): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-5 flex items-center space-x-2">
        <i class="fa-solid fa-circle-check text-emerald-500"></i>
        <span><?php echo e(session('success')); ?></span>
    </div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm mb-5">
        <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/partials/alerts.blade.php ENDPATH**/ ?>