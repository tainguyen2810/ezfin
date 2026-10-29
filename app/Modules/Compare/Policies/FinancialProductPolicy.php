<?php

namespace App\Modules\Compare\Policies;

use App\Models\User;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;

/** Vận hành dữ liệu và quản trị cao nhất nhập liệu, xuất bản; biên tập chỉ xem; chỉ quản trị cao nhất được xóa. */
class FinancialProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value, Role::Editor->value]);
    }

    public function view(User $user, FinancialProduct $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value]);
    }

    public function update(User $user, FinancialProduct $product): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FinancialProduct $product): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
