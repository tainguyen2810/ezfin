<?php

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\NotificationSetting;

/** Cài đặt thông báo: chỉ quản trị cao nhất xem và sửa. Danh sách loại do code khai báo nên không tạo/xóa. */
class NotificationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value);
    }

    public function view(User $user, NotificationSetting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, NotificationSetting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, NotificationSetting $setting): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
