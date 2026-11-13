<?php

namespace App\Modules\Newsletter\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Newsletter\Models\Subscriber;

/** Danh sách email là dữ liệu cá nhân: chỉ quản trị cao nhất xem và xóa (khi có yêu cầu). Không tạo, sửa tay. */
class SubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value);
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        return false;
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
