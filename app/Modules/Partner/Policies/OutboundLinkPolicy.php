<?php

namespace App\Modules\Partner\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Partner\Models\OutboundLink;

/** Liên kết đối tác: quản trị cao nhất và vai trò đối tác/tài chính. Link đã có click thì chỉ tắt, không xóa. */
class OutboundLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::Finance->value]);
    }

    public function view(User $user, OutboundLink $link): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, OutboundLink $link): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, OutboundLink $link): bool
    {
        return $this->viewAny($user) && ! $link->clicks()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
