<?php
    $classes = [
        'low' => 'text-slate-500',
        'medium' => 'text-sky-600',
        'high' => 'text-orange-600 font-semibold',
        'urgent' => 'text-rose-600 font-bold',
    ];
?>
<span class="text-xs <?php echo e($classes[$priority] ?? 'text-slate-500'); ?>">
    <i class="fa-solid fa-flag text-[10px] mr-1"></i><?php echo e(\App\Enums\TicketPriority::tryFrom($priority)?->label() ?? $priority); ?>

</span>
<?php /**PATH /var/www/html/resources/views/partials/priority-badge.blade.php ENDPATH**/ ?>