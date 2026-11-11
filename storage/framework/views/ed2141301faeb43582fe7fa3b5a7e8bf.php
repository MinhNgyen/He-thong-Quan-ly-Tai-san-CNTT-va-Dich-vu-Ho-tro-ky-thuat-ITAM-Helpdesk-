<a href="<?php echo e(route('notifications.index')); ?>" class="relative p-2 text-slate-500 hover:text-brand-600" title="Thông báo" aria-label="Thông báo<?php echo e(($unreadCount ?? 0) > 0 ? ', ' . $unreadCount . ' chưa đọc' : ''); ?>">
    <i class="fa-regular fa-bell" aria-hidden="true"></i>
    <?php if(($unreadCount ?? 0) > 0): ?>
        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"><?php echo e($unreadCount > 99 ? '99+' : $unreadCount); ?></span>
    <?php endif; ?>
</a>
<?php /**PATH /var/www/html/resources/views/partials/bell.blade.php ENDPATH**/ ?>