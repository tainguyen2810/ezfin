<?php

namespace App\Modules\Partner\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Partner\Models\AffiliateConversion;

/** Chuyển đổi và doanh thu đối tác: quản trị cao nhất và vai trò đối tác/tài chính. Không xóa (sổ sách), chỉ đổi trạng thái. */
class AffiliateConversionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::Finance->value]);
    }

    public function view(User $user, AffiliateConversion $conversion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AffiliateConversion $conversion): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AffiliateConversion $conversion): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
