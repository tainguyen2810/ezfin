<?php

namespace App\Modules\Core\Policies;

use App\Models\User;

/** Chỉ quản trị cao nhất quản lý tài khoản; không ai tự xóa chính mình. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
