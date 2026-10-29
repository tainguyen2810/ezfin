<?php

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;

/** Nhật ký chỉ xem, chỉ quản trị cao nhất; không ai được tạo, sửa, xóa qua giao diện. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
