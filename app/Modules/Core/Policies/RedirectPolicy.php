<?php

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Redirect;

/** Chuyển hướng URL là việc SEO: biên tập viên và quản trị cao nhất quản lý. */
class RedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::Editor->value]);
    }

    public function view(User $user, Redirect $redirect): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Redirect $redirect): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
