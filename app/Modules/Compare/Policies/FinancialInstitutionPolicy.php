<?php

namespace App\Modules\Compare\Policies;

use App\Models\User;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Core\Enums\Role;

/** Vận hành dữ liệu và quản trị cao nhất nhập liệu; biên tập chỉ xem; chỉ quản trị cao nhất được xóa. */
class FinancialInstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value, Role::Editor->value]);
    }

    public function view(User $user, FinancialInstitution $institution): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::DataOperator->value]);
    }

    public function update(User $user, FinancialInstitution $institution): bool
    {
        return $this->create($user);
    }

    /** Tổ chức còn sản phẩm thì không xóa được (khóa ngoại cũng chặn). */
    public function delete(User $user, FinancialInstitution $institution): bool
    {
        return $user->isSuperAdmin() && ! $institution->products()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
