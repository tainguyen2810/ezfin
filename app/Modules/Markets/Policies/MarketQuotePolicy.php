<?php

namespace App\Modules\Markets\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Eloquent\Model;

/** Tỷ giá, giá vàng: vận hành dữ liệu và quản trị cao nhất nhập, sửa, xóa dòng nhập nhầm; biên tập chỉ xem. */
class MarketQuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value, Role::Editor->value]);
    }

    public function view(User $user, Model $quote): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value]);
    }

    public function update(User $user, Model $quote): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Model $quote): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
