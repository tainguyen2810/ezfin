<?php

namespace App\Modules\DataSync\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Eloquent\Model;

/** Nguồn dữ liệu và bản nháp: quản trị cao nhất, vận hành dữ liệu xem, chạy, duyệt; biên tập chỉ xem. Không tạo/xóa tay. */
class DataSyncPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value, Role::Editor->value]);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    /** Bật/tắt nguồn, chạy ngay, duyệt hoặc từ chối bản nháp. */
    public function update(User $user, Model $model): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value]);
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
