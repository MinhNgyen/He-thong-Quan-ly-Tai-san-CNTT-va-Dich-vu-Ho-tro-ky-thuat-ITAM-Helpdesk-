<?php $__env->startSection('title', ($editing ? 'Sửa' : 'Thêm') . ' người dùng - ITAM Helpdesk'); ?>
<?php $__env->startSection('page_title', $editing ? 'Sửa người dùng' : 'Thêm người dùng'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $field = 'w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500';
?>
<div class="max-w-2xl bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <form method="POST" action="<?php echo e($editing ? route('admin.users.update', $editUser) : route('admin.users.store')); ?>" class="grid sm:grid-cols-2 gap-4">
        <?php echo csrf_field(); ?>
        <?php if($editing): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

        <?php if (! ($editing)): ?>
        <div>
            <label for="employee_code" class="block text-sm font-medium text-slate-700 mb-1">Mã nhân viên</label>
            <input id="employee_code" name="employee_code" value="<?php echo e(old('employee_code')); ?>" required class="<?php echo e($field); ?>">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required class="<?php echo e($field); ?>">
        </div>
        <?php else: ?>
        <div class="sm:col-span-2 text-sm text-slate-600">Mã nhân viên <strong><?php echo e($editUser->employee_code); ?></strong> · Email <strong><?php echo e($editUser->email); ?></strong> (không thay đổi được)</div>
        <?php endif; ?>

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Họ tên</label>
            <input id="name" name="name" value="<?php echo e(old('name', $editUser->name)); ?>" required class="<?php echo e($field); ?>">
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Điện thoại</label>
            <input id="phone" name="phone" value="<?php echo e(old('phone', $editUser->phone)); ?>" class="<?php echo e($field); ?>">
        </div>
        <div>
            <label for="role_id" class="block text-sm font-medium text-slate-700 mb-1">Vai trò</label>
            <select id="role_id" name="role_id" required class="<?php echo e($field); ?>">
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($role->id); ?>" <?php if(old('role_id', $editUser->role_id) == $role->id): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-slate-700 mb-1">Phòng ban</label>
            <select id="department_id" name="department_id" class="<?php echo e($field); ?>">
                <option value="">— Không —</option>
                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($d->id); ?>" <?php if(old('department_id', $editUser->department_id) == $d->id): echo 'selected'; endif; ?>><?php echo e($d->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1"><?php echo e($editing ? 'Đặt lại mật khẩu (bỏ trống nếu giữ nguyên)' : 'Mật khẩu'); ?></label>
            <input id="password" type="password" name="password" autocomplete="new-password" <?php if(! $editing): echo 'required'; endif; ?> aria-describedby="pw-help" class="<?php echo e($field); ?>">
            <p id="pw-help" class="text-xs text-slate-600 mt-1">Tối thiểu 8 ký tự, gồm cả chữ và số.</p>
        </div>
        <div class="sm:col-span-2 flex items-center space-x-2">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" <?php if(old('is_active', $editUser->is_active)): echo 'checked'; endif; ?> class="w-4 h-4">
            <label for="is_active" class="text-sm text-slate-700">Tài khoản đang hoạt động (bỏ chọn để khóa)</label>
        </div>
        <div class="sm:col-span-2 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm rounded-xl">Lưu</button>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm rounded-xl">Hủy</a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/users/form.blade.php ENDPATH**/ ?>