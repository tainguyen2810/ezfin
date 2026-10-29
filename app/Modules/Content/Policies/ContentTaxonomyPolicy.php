<?php

namespace App\Modules\Content\Policies;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Eloquent\Model;

/**
 * Tác giả và chuyên mục: biên tập viên và quản trị cao nhất quản lý; chỉ quản trị cao nhất được xóa.
 *
 * @template TModel of \App\Modules\Content\Models\Author|\App\Modules\Content\Models\ArticleCategory
 */
class ContentTaxonomyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::Editor->value]);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    /** Không xóa khi còn bài viết (kể cả bài đã xóa tạm) để bài không mất chuyên mục hay tác giả. */
    public function delete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin() && ! $model->articles()->withTrashed()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
